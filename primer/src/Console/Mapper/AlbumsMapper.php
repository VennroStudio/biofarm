<?php

declare(strict_types=1);

namespace App\Console\Mapper;

use App\Components\Helper;
use App\Modules\Entity\Album\Album;
use App\Modules\Entity\Album\AlbumRepository;
use App\Modules\Entity\AlbumArtist\AlbumArtist;
use App\Modules\Entity\AppleAlbum\AppleAlbum;
use App\Modules\Entity\AppleAlbumArtist\AppleAlbumArtist;
use App\Modules\Entity\Artist\Artist;
use App\Modules\Entity\Artist\ArtistRepository;
use App\Modules\Entity\TidalAlbum\TidalAlbum;
use App\Modules\Entity\TidalAlbum\TidalAlbumRepository;
use App\Modules\Entity\TidalAlbumArtist\TidalAlbumArtist;
use App\Modules\Query\ISRC\ISRCByAlbum;
use App\Modules\Query\ISRC\ISRCByAppleAlbum;
use App\Modules\Query\ISRC\ISRCByTidalAlbum;
use App\Modules\Typesense\Album\AlbumCollection;
use App\Modules\Typesense\Album\AlbumDocument;
use App\Modules\Typesense\Album\AlbumQuery;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Throwable;
use ZayMedia\Shared\Components\Transliterator\Transliterator;

readonly class AlbumsMapper
{
    public function __construct(
        private Connection $connection,
        private EntityManagerInterface $em,
        private Transliterator $transliterator,
        private ArtistRepository $artistRepository,
        private AlbumRepository $albumRepository,
        private TidalAlbumRepository $tidalAlbumRepository,
        private ISRCByAlbum\Fetcher $isrcByAlbumFetcher,
        private ISRCByTidalAlbum\Fetcher $isrcByTidalAlbumFetcher,
        private ISRCByAppleAlbum\Fetcher $isrcByAppleAlbumFetcher,
        private AlbumCollection $albumCollection,
        private TracksMapper $tracksMapper
    ) {}

    /** @throws \Exception */
    public function handle(Artist $artist, ?int $collectionNumber): void
    {
        if (null !== $collectionNumber) {
            $this->albumCollection->setNumber($collectionNumber);
        }

        $this->prepareTypesense($artist->getId());
        $this->mapAlbums($artist->getId(), $collectionNumber);

        $artist->setMerged();
        $this->artistRepository->add($artist);
        $this->em->flush();
    }

    /** @throws \Exception */
    private function mapAlbums(int $artistId, ?int $collectionNumber): void
    {
        $this->albumRepository->resetAllNotApproved($artistId);

        $this->mapTidalAlbums($artistId, $collectionNumber);
        $this->mapAppleAlbums($artistId, $collectionNumber);
    }

    /** @throws \Exception */
    private function mapTidalAlbums(int $artistId, ?int $collectionNumber): void
    {
        $tidalAlbums = $this->getTidalAlbums($artistId);

        foreach ($tidalAlbums as $tidalAlbum) {
            $ids = $this->albumCollection->searchIdentifiers(
                new AlbumQuery(
                    search: $tidalAlbum->getBarcodeId() . ' ' . $tidalAlbum->getName(),
                    artistId: $artistId,
                    isAlbum: null,
                    limit: 5
                )
            );

            $possibleAlbums = [];

            foreach ($ids as $id) {
                if (!$albumPossible = $this->albumRepository->findById($id)) {
                    continue;
                }

                if ($albumPossible->isApproved()) {
                    continue;
                }

                $diffTrack = abs($albumPossible->getSpotifyTotalTracks() - $tidalAlbum->getTotalTracks());

                if ($diffTrack <= 1) {
                    $possibleAlbums[] = $albumPossible;
                }
            }

            if (\count($possibleAlbums) === 0) {
                continue;
            }

            $approvedKey = null;

            foreach ($possibleAlbums as $key => $album) {
                //  $isApproved = $this->isApproved(
                //      spotifyName: $album->getSpotifyName(),
                //      spotifyTotalTracks: $album->getSpotifyTotalTracks(),
                //      tidalName: $tidalAlbum->getName(),
                //      tidalTotalTracks: $tidalAlbum->getTotalTracks()
                //  );

                $isApproved = $this->isApprovedTidalByTracks(
                    albumId: $album->getId(),
                    tidalAlbumId: $tidalAlbum->getId()
                );

                if ($isApproved) {
                    $approvedKey = $key;
                    break;
                }
            }

            $album = $possibleAlbums[0];
            $album->setNotApproved();

            if (null !== $approvedKey) {
                $album = $possibleAlbums[$approvedKey];
                $album->setApproved();
            }

            $album->setTidalAlbumId($tidalAlbum->getId());

            $this->albumRepository->add($album);
            $this->em->flush();

            $this->tracksMapper->handle($album, $collectionNumber);
        }
    }

    /** @throws \Exception */
    private function mapAppleAlbums(int $artistId, ?int $collectionNumber): void
    {
        $appleAlbums = $this->getAppleAlbums($artistId);

        foreach ($appleAlbums as $appleAlbum) {
            $ids = $this->albumCollection->searchIdentifiers(
                new AlbumQuery(
                    search: ($appleAlbum->getUpc() ?? '') . ' ' . $appleAlbum->getName(),
                    artistId: $artistId,
                    isAlbum: null,
                    limit: 5
                )
            );

            $possibleAlbums = [];

            foreach ($ids as $id) {
                if (!$albumPossible = $this->albumRepository->findById($id)) {
                    continue;
                }

                if (!$albumPossible->isAllTracksMapped()) {
                    continue;
                }

                $isApproved = $this->isApprovedAppleByTracks(
                    albumId: $albumPossible->getId(),
                    appleAlbumId: $appleAlbum->getId()
                );

                if ($isApproved) {
                    $possibleAlbums[] = $albumPossible;
                }
            }

            if (\count($possibleAlbums) === 0) {
                continue;
            }

            $appleId = null;
            $album = null;

            foreach ($possibleAlbums as $possibleAlbum) {
                $tidalAlbumId = $possibleAlbum->getTidalAlbumId();
                if (null === $tidalAlbumId) {
                    continue;
                }

                $tidalAlbum = $this->tidalAlbumRepository->findById($tidalAlbumId);
                if (null === $tidalAlbum) {
                    continue;
                }

                if (\in_array($appleAlbum->getUpc(), [$possibleAlbum->getSpotifyUPC() ?? '', $tidalAlbum->getBarcodeId()], true)) {
                    $appleId = $appleAlbum->getId();
                    $album = $possibleAlbum;
                    break;
                }
            }

            if (null === $album || null === $appleId) {
                continue;
            }

            if (null !== $album->getLoAlbumId()) {
                $album->setAppleFound();
            }
            $album->setAppleAlbumId($appleId);

            $this->albumRepository->add($album);
            $this->em->flush();

            $this->tracksMapper->handle($album, $collectionNumber);
        }
    }

    private function isApprovedTidalByTracks(int $albumId, int $tidalAlbumId): bool
    {
        $arr = array_intersect(
            $this->isrcByAlbumFetcher->fetch($albumId),
            $this->isrcByTidalAlbumFetcher->fetch($tidalAlbumId)
        );

        return \count($arr) > 0;
    }

    private function isApprovedAppleByTracks(int $albumId, int $appleAlbumId): bool
    {
        $spotify = $this->isrcByAlbumFetcher->fetch($albumId);
        $apple = $this->isrcByAppleAlbumFetcher->fetch($appleAlbumId);

        sort($spotify);
        sort($apple);

        return \count(array_diff($spotify, $apple)) === 0;
    }

    private function isApproved(string $spotifyName, int $spotifyTotalTracks, string $tidalName, int $tidalTotalTracks): bool
    {
        $spotifyName = mb_strtolower(trim($spotifyName), 'UTF-8');
        $tidalName = mb_strtolower(trim($tidalName), 'UTF-8');

        $spotifyName = Helper::textFormatter($spotifyName);
        $tidalName = Helper::textFormatter($tidalName);

        $spotifyNameTranslit = $this->transliterator->translit($spotifyName);
        $spotifyNameTranslitICAO = Helper::translitTidal($spotifyName);

        if (
            $spotifyName !== $tidalName &&
            $spotifyNameTranslit !== $tidalName &&
            $spotifyNameTranslitICAO !== $tidalName
        ) {
            return false;
        }

        if ($spotifyTotalTracks !== $tidalTotalTracks) {
            return false;
        }

        return true;
    }

    /** @return TidalAlbum[] */
    private function getTidalAlbums(int $artistId): array
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('ta')
            ->from(TidalAlbum::class, 'ta')
            ->innerJoin(TidalAlbumArtist::class, 'taa', Join::WITH, 'ta.id = taa.albumId AND taa.artistId = :artistId')
            ->leftJoin(Album::class, 'a', Join::WITH, 'a.tidalAlbumId = ta.id')
            ->where('a.tidalAlbumId IS NULL')
            ->andWhere('ta.isDeleted = false')
            ->setParameter('artistId', $artistId)
            ->orderBy('ta.id', 'ASC');

        /** @var TidalAlbum[] $albums */
        $albums = $queryBuilder->getQuery()->getResult();

        $items = [];

        foreach ($albums as $album) {
            $items[] = $album;
        }

        return $items;
    }

    /** @return AppleAlbum[] */
    private function getAppleAlbums(int $artistId): array
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('aa')
            ->from(AppleAlbum::class, 'aa')
            ->innerJoin(AppleAlbumArtist::class, 'aaa', Join::WITH, 'aa.id = aaa.albumId AND aaa.artistId = :artistId')
            ->leftJoin(Album::class, 'a', Join::WITH, 'a.appleAlbumId = aa.id')
            ->where('a.appleAlbumId IS NULL')
            ->andWhere('aa.isDeleted = false')
            ->setParameter('artistId', $artistId)
            ->orderBy('aa.id', 'ASC');

        /** @var AppleAlbum[] $albums */
        $albums = $queryBuilder->getQuery()->getResult();

        $items = [];

        foreach ($albums as $album) {
            $items[] = $album;
        }

        return $items;
    }

    private function prepareTypesense(int $artistId): void
    {
        $this->recreateSchema();

        try {
            $this->albumCollection->upsertDocuments(
                $this->getDocuments($artistId)
            );
        } catch (\Exception) {
        }
    }

    private function recreateSchema(): void
    {
        try {
            $this->albumCollection->deleteSchema();
        } catch (Throwable) {
        }

        try {
            $this->albumCollection->createSchema();
        } catch (Throwable) {
        }
    }

    /**
     * @return AlbumDocument[]
     * @throws Exception
     */
    private function getDocuments(int $artistId): array
    {
        $documents = [];

        $sqlQuery = $this->connection->createQueryBuilder()
            ->select(['a.id', 'a.spotify_upc', 'a.spotify_name', 'a.spotify_type', 'a.spotify_total_tracks'])
            ->from(Album::DB_NAME, 'a')
            ->leftJoin('a', AlbumArtist::DB_NAME, 'aa', 'a.id = aa.album_id')
            ->andWhere('aa.artist_id = :artistId')
            ->setParameter('artistId', $artistId);

        $result = $sqlQuery
            ->orderBy('a.id', 'ASC')
            ->executeQuery();

        /** @var array{
         *     id: int,
         *     spotify_upc: string,
         *     spotify_name: string,
         *     spotify_type: string,
         *     spotify_total_tracks: int,
         * }[] $rows
         */
        $rows = $result->fetchAllAssociative();

        foreach ($rows as $row) {
            $documents[] = new AlbumDocument(
                id: $row['id'],
                artistIds: [$artistId],
                name: $row['spotify_upc'] . ' ' . mb_strtolower($row['spotify_name'], 'UTF-8'),
                nameTranslit: mb_strtolower($this->transliterator->translit($row['spotify_name']), 'UTF-8'),
                isAlbum: $row['spotify_type'] === 'album',
                totalTracks: $row['spotify_total_tracks']
            );
        }

        return $documents;
    }
}
