<?php

declare(strict_types=1);

namespace App\Console\Mapper;

use App\Components\Helper;
use App\Modules\Entity\Album\Album;
use App\Modules\Entity\Album\AlbumRepository;
use App\Modules\Entity\AppleTrack\AppleTrack;
use App\Modules\Entity\TidalTrack\TidalTrack;
use App\Modules\Entity\TidalTrack\TidalTrackRepository;
use App\Modules\Entity\Track\Track;
use App\Modules\Entity\Track\TrackRepository;
use App\Modules\Typesense\Track\TrackCollection;
use App\Modules\Typesense\Track\TrackDocument;
use App\Modules\Typesense\Track\TrackQuery;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Throwable;
use ZayMedia\Shared\Components\Transliterator\Transliterator;

readonly class TracksMapper
{
    public function __construct(
        private Connection $connection,
        private EntityManagerInterface $em,
        private Transliterator $transliterator,
        private AlbumRepository $albumRepository,
        private TrackRepository $trackRepository,
        private TidalTrackRepository $tidalTrackRepository,
        private TrackCollection $trackCollection,
    ) {}

    /** @throws \Exception */
    public function handle(Album $album, ?int $collectionNumber): void
    {
        if (null !== $collectionNumber) {
            $this->trackCollection->setNumber($collectionNumber);
        }

        $this->prepareTypesense($album->getId());
        $this->mapTracks($album);

        $isAllTracksMapped = $this->isAllTracksMapped($album);

        $album->setMergedAt(time());
        $album->setAllTracksMapped($isAllTracksMapped);

        $this->albumRepository->add($album);
        $this->em->flush();
    }

    /** @throws \Exception */
    private function mapTracks(Album $album): void
    {
        $this->mapTidalTracks($album);
        $this->mapAppleTracks($album);
    }

    /** @throws \Exception */
    private function mapTidalTracks(Album $album): void
    {
        $tidalAlbumId = $album->getTidalAlbumId();

        if (null === $tidalAlbumId || !$album->isApproved()) {
            return;
        }

        $tidalTracks = $this->getTidalTracks($tidalAlbumId);

        foreach ($tidalTracks as $tidalTrack) {
            $ids = $this->trackCollection->searchIdentifiers(
                new TrackQuery(
                    search: $tidalTrack->getIsrc() . ' ' . $tidalTrack->getName(),
                    albumId: $album->getId(),
                    isrc: $tidalTrack->getIsrc(),
                    diskNumber: null,
                    limit: 5
                )
            );

            $possibleTracks = [];

            foreach ($ids as $id) {
                if (!$trackPossible = $this->trackRepository->findById($id)) {
                    continue;
                }

                if ($trackPossible->isApproved()) {
                    continue;
                }

                $possibleTracks[] = $trackPossible;
            }

            if (\count($possibleTracks) === 0) {
                continue;
            }

            $approvedKey = null;

            foreach ($possibleTracks as $key => $track) {
                $isApproved = $this->isApproved(
                    spotifyName: $track->getSpotifyName(),
                    spotifyDiskNumber: $track->getSpotifyDiskNumber(),
                    spotifyTrackNumber: $track->getSpotifyTrackNumber(),
                    spotifyISRC: $track->getSpotifyISRC(),
                    tidalName: $tidalTrack->getName(),
                    tidalDiskNumber: $tidalTrack->getDiskNumber(),
                    tidalTrackNumber: $tidalTrack->getTrackNumber(),
                    tidalISRC: $tidalTrack->getIsrc()
                );

                if ($isApproved) {
                    $approvedKey = $key;
                    break;
                }
            }

            $track = $possibleTracks[0];
            $track->setNotApproved();

            if (null !== $approvedKey) {
                $track = $possibleTracks[$approvedKey];
                $track->setApproved();
            }

            $track->setTidalTrackId($tidalTrack->getId());

            $this->trackRepository->add($track);
            $this->em->flush();
        }
    }

    /** @throws \Exception */
    private function mapAppleTracks(Album $album): void
    {
        $appleAlbumId = $album->getAppleAlbumId();

        if (null === $appleAlbumId) {
            return;
        }

        $appleTracks = $this->getAppleTracks($appleAlbumId);

        foreach ($appleTracks as $appleTrack) {
            $ids = $this->trackCollection->searchIdentifiers(
                new TrackQuery(
                    search: $appleTrack->getIsrc() . ' ' . $appleTrack->getName(),
                    albumId: $album->getId(),
                    isrc: $appleTrack->getIsrc(),
                    diskNumber: $appleTrack->getDiskNumber(),
                    limit: 5
                )
            );

            $possibleTracks = [];

            foreach ($ids as $id) {
                if (!$trackPossible = $this->trackRepository->findById($id)) {
                    continue;
                }

                $possibleTracks[] = $trackPossible;
            }

            if (\count($possibleTracks) === 0) {
                continue;
            }

            $appleId = null;
            $track = null;

            foreach ($possibleTracks as $possibleTrack) {
                $tidalTrackId = $possibleTrack->getTidalTrackId();
                if (null === $tidalTrackId) {
                    continue;
                }

                $tidalTrack = $this->tidalTrackRepository->findById($tidalTrackId);
                if (null === $tidalTrack) {
                    continue;
                }

                if (\in_array($appleTrack->getIsrc(), [$possibleTrack->getSpotifyISRC() ?? '', $tidalTrack->getIsrc()], true)) {
                    $appleId = $appleTrack->getId();
                    $track = $possibleTrack;
                    break;
                }
            }

            if (null === $track || null === $appleId) {
                continue;
            }

            if (null !== $track->getLoTrackId()) {
                $track->setAppleFound();
            }
            $track->setAppleTrackId($appleId);

            $this->trackRepository->add($track);
            $this->em->flush();
        }
    }

    private function isAllTracksMapped(Album $album): bool
    {
        $countAll = $this->trackRepository->getCountByAlbumId($album->getId());

        if ($countAll === 0) {
            return false;
        }

        $countApprovedAll = $this->trackRepository->getCountApprovedByAlbumId($album->getId());

        return $countAll === $countApprovedAll && $countAll === $album->getSpotifyTotalTracks();
    }

    private function isApproved(
        string $spotifyName,
        int $spotifyDiskNumber,
        int $spotifyTrackNumber,
        ?string $spotifyISRC,
        string $tidalName,
        int $tidalDiskNumber,
        int $tidalTrackNumber,
        ?string $tidalISRC,
    ): bool {
        // if ($spotifyDiskNumber !== $tidalDiskNumber) {
        //    return false;
        // }

        if ($spotifyTrackNumber !== $tidalTrackNumber) {
            return false;
        }

        if (null === $spotifyISRC && null === $tidalISRC) {
            $spotifyName = mb_strtolower(trim($spotifyName), 'UTF-8');
            $tidalName = mb_strtolower(trim($tidalName), 'UTF-8');

            $spotifyName = Helper::textFormatter($spotifyName);
            $tidalName = Helper::textFormatter($tidalName);

            $spotifyNameTranslit = $this->transliterator->translit($spotifyName);

            if (
                $spotifyName !== $tidalName &&
                $spotifyNameTranslit !== $tidalName &&
                $this->formatSongTitle($spotifyName) !== $tidalName
            ) {
                return false;
            }
        }

        if (
            null !== $spotifyISRC &&
            null !== $tidalISRC &&
            $spotifyISRC !== $tidalISRC
        ) {
            return false;
        }

        return true;
    }

    /** @return TidalTrack[] */
    private function getTidalTracks(int $tidalAlbumId): array
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('tt')
            ->from(TidalTrack::class, 'tt')
            ->leftJoin(Track::class, 't', Join::WITH, 't.tidalTrackId = tt.id')
            ->where('t.tidalTrackId IS NULL')
            ->andWhere('tt.tidalAlbumId = :tidalAlbumId')
            ->setParameter('tidalAlbumId', $tidalAlbumId)
            ->orderBy('tt.id', 'ASC');

        /** @var TidalTrack[] $tracks */
        $tracks = $queryBuilder->getQuery()->getResult();

        $items = [];

        foreach ($tracks as $track) {
            $items[] = $track;
        }

        return $items;
    }

    /** @return AppleTrack[] */
    private function getAppleTracks(int $appleAlbumId): array
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('at')
            ->from(AppleTrack::class, 'at')
            ->leftJoin(Track::class, 't', Join::WITH, 't.appleTrackId = at.id')
            ->where('t.appleTrackId IS NULL')
            ->andWhere('at.appleAlbumId = :appleAlbumId')
            ->setParameter('appleAlbumId', $appleAlbumId)
            ->orderBy('at.id', 'ASC');

        /** @var AppleTrack[] $tracks */
        $tracks = $queryBuilder->getQuery()->getResult();

        $items = [];

        foreach ($tracks as $track) {
            $items[] = $track;
        }

        return $items;
    }

    private function prepareTypesense(int $albumId): void
    {
        $this->recreateSchema();

        try {
            $this->trackCollection->upsertDocuments(
                $this->getDocuments($albumId)
            );
        } catch (\Exception) {
        }
    }

    private function recreateSchema(): void
    {
        try {
            $this->trackCollection->deleteSchema();
        } catch (Throwable) {
        }

        try {
            $this->trackCollection->createSchema();
        } catch (Throwable) {
        }
    }

    /**
     * @return TrackDocument[]
     * @throws Exception
     */
    private function getDocuments(int $albumId): array
    {
        $documents = [];

        $sqlQuery = $this->connection->createQueryBuilder()
            ->select(['a.id', 'a.spotify_name', 'a.spotify_disk_number', 'a.spotify_isrc'])
            ->from(Track::DB_NAME, 'a')
            ->andWhere('a.album_id = :albumId')
            ->setParameter('albumId', $albumId);

        $result = $sqlQuery
            ->orderBy('a.id', 'ASC')
            ->executeQuery();

        /** @var array{
         *     id: int,
         *     spotify_name: string,
         *     spotify_disk_number: int,
         *     spotify_isrc: string,
         * }[] $rows
         */
        $rows = $result->fetchAllAssociative();

        foreach ($rows as $row) {
            $documents[] = new TrackDocument(
                id: $row['id'],
                albumId: $albumId,
                isrc: $row['spotify_isrc'],
                name: $row['spotify_isrc'] . ' ' . mb_strtolower($row['spotify_name'], 'UTF-8'),
                nameTranslit: mb_strtolower($this->transliterator->translit($row['spotify_name']), 'UTF-8'),
                diskNumber: $row['spotify_disk_number']
            );
        }

        return $documents;
    }

    private function formatSongTitle(string $title): string
    {
        if (str_contains($title, ') - ')) {
            $transformedString = str_replace(') - ', ') [', $title);
            $transformedString = rtrim($transformedString, ' ') . ']';
        } elseif (str_contains($title, ' - ')) {
            $transformedString = str_replace(' - ', ' (', $title);
            $transformedString = rtrim($transformedString, ' ') . ')';
        } else {
            $transformedString = $title;
        }
        return $transformedString;
    }
}
