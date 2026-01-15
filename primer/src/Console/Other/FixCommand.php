<?php

declare(strict_types=1);

namespace App\Console\Other;

use App\Components\AppleGrab\AppleGrab;
use App\Components\SpotifyGrab\SpotifyGrab;
use App\Components\TidalGrab\TidalDL;
use App\Components\TidalGrab\TidalGrab;
use App\Console\Loader\AlbumLoader;
use App\Modules\Command\Spotify;
use App\Modules\Command\Tidal;
use App\Modules\Constant;
use App\Modules\Entity\Album\Album;
use App\Modules\Entity\Album\AlbumRepository;
use App\Modules\Entity\AppleAlbum\AppleAlbum;
use App\Modules\Entity\Artist\ArtistRepository;
use App\Modules\Entity\TidalAlbum\TidalAlbum;
use App\Modules\Entity\TidalToken\TidalToken;
use App\Modules\Entity\TidalToken\TidalTokenRepository;
use App\Modules\Entity\TidalTrack\TidalTrack;
use App\Modules\Entity\Track\Track;
use App\Modules\Query\GetAlbumArtists;
use App\Modules\Query\GetSpotifyToken;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class FixCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Tidal\RefreshAlbum\Handler $tidalAlbumCreator,
        private readonly Tidal\RefreshTrack\Handler $tidalTrackCreator,
        private readonly Spotify\RefreshAlbum\Handler $spotifyAlbumCreator,
        private readonly Spotify\RefreshTrack\Handler $spotifyTrackCreator,
        private readonly AlbumLoader $albumLoader,
        private readonly AlbumRepository $albumRepository,
        private readonly ArtistRepository $artistRepository,
        private readonly GetAlbumArtists\Fetcher $getAlbumArtistsFetcher,
        private readonly TidalTokenRepository $tidalTokenRepository,
        private readonly TidalGrab $tidalGrab,
        private readonly GetSpotifyToken\Fetcher $spotifyTokenFetcher,
        private readonly SpotifyGrab $spotifyGrab,
        private readonly AppleGrab $appleGrab,
        private readonly TidalDL $tidalDL,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('other:fix')
            ->setDescription('Fix command')
            ->addArgument('token', InputArgument::OPTIONAL, 'token?');
    }

    /** @throws Throwable */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $token = null;
        if (null !== $input->getArgument('token')) {
            $token = (int)$input->getArgument('token');
        }

        /**
         * TODO: БД LO
         * 1. Дубли треков в альбоме
         * SELECT `album_id`, count(*) as cnt FROM `audios` WHERE deleted_at IS NULL AND isrc IS NOT NULL GROUP BY `album_id`, `isrc` HAVING cnt > 1;
         * 2. Дубли альбомов у артиста.
         */
        try {
            $this->normalizeDeletedTracks($output);

            $this->setTidalDLToken($token ?? 100);
            $this->tryLoadTidalNotFoundTracks($output);

            $this->setSpotifySettings();
            $this->fixesSpotifyAlbums($output);
            $this->fixesSpotifyTracks($output);
            $this->fixesSpotifyUPC($output);

            $this->setTidalSettings();
            $this->fixesTidalAlbums($output);
            $this->fixesTidalTracks($output);

            $this->setAppleSettings();
            $this->fixesAppleAlbums($output);
        } catch (Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        return 0;
    }

    /** @throws Exception */
    private function setSpotifySettings(): void
    {
        $token = $this->spotifyTokenFetcher->fetch();

        if (null === $token) {
            throw new Exception('NO SPOTIFY ACCESS TOKEN');
        }

        $this->spotifyGrab->setAccessToken($token);
    }

    /** @throws Exception */
    private function fixesSpotifyAlbums(OutputInterface $output): void
    {
        $this->em->clear();

        $sql = 'UPDATE albums SET spotify_available_markets = NULL WHERE spotify_available_markets = "[]"';
        $this->em->getConnection()->executeQuery($sql);

        $sql = 'UPDATE albums SET spotify_images = NULL WHERE spotify_images = "[]"';
        $this->em->getConnection()->executeQuery($sql);

        $sql = 'UPDATE albums SET spotify_copyrights = NULL WHERE spotify_copyrights = "[]"';
        $this->em->getConnection()->executeQuery($sql);

        $sql = 'UPDATE albums SET spotify_genres = NULL WHERE spotify_genres = "[]"';
        $this->em->getConnection()->executeQuery($sql);

        $output->writeln('<info>Spotify albums metadata fixes!</info>');

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select('a')
            ->from(Album::class, 'a')
            ->andWhere('a.spotifyId IS NOT NULL')
            ->andWhere('a.spotifyName = :empty OR a.spotifyReleasedPrecision IS NULL OR a.spotifyUPC IS NULL OR a.spotifyExternalIds IS NULL')
            ->andWhere('a.spotifyIsDeleted = false')
            ->setParameter('empty', '')
            ->orderBy('a.id', 'ASC');

        $spotifyAlbumIds = [];

        /** @var Album[] $albums */
        $albums = $queryBuilder->getQuery()->getResult();

        foreach ($albums as $album) {
            if (null === $album->getSpotifyId()) {
                continue;
            }

            $spotifyAlbumIds[] = $album->getSpotifyId();
        }

        $items = $this->spotifyGrab->getAlbumByIds($spotifyAlbumIds);

        $spotifyAlbumIdsUpdated = [];
        $i = 0;

        foreach ($items as $item) {
            $this->spotifyAlbumCreator->handle(
                new Spotify\RefreshAlbum\Command(
                    artistId: null,
                    spotifyArtistIds: [],
                    albumId: $item->id,
                    type: $item->type,
                    name: $item->name,
                    releasedAt: $item->release,
                    releasedPrecision: $item->releasePrecision,
                    totalTracks: $item->totalTracks,
                    availableMarkets: $item->availableMarkets,
                    artists: $item->artists,
                    upc: $item->upc,
                    images: $item->images,
                    copyrights: $item->copyrights,
                    externalIds: $item->externalIds,
                    genres: $item->genres,
                    label: $item->label,
                    popularity: $item->popularity
                )
            );

            $spotifyAlbumIdsUpdated[] = $item->id;

            $output->writeln('<info>' . (++$i) . ') [' . $item->id . '] ' . $item->name . ' - Spotify album metadata fixes!</info>');
        }
        $output->writeln('<info>Spotify albums fixes! Count: ' . \count($spotifyAlbumIdsUpdated) . ' / ' . \count($albums) . '</info>');
    }

    /** @throws Exception */
    private function fixesSpotifyTracks(OutputInterface $output): void
    {
        $this->em->clear();

        $sql = 'UPDATE tracks SET spotify_isrc = NULL WHERE spotify_isrc = ""';
        $this->em->getConnection()->executeQuery($sql);

        $sql = 'UPDATE tracks SET spotify_type = NULL WHERE spotify_type = ""';
        $this->em->getConnection()->executeQuery($sql);

        $sql = 'UPDATE tracks SET spotify_available_markets = NULL WHERE spotify_available_markets = "[]"';
        $this->em->getConnection()->executeQuery($sql);

        $output->writeln('<info>Spotify tracks metadata fixes!</info>');

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select('t')
            ->from(Track::class, 't')
            ->andWhere('t.spotifyId IS NOT NULL')
            ->andWhere('t.spotifyName = :empty OR t.spotifyISRC IS NULL OR t.spotifyType IS NULL')
            ->andWhere('t.spotifyIsDeleted = false AND t.spotifyOriginalId IS NULL')
            ->setParameter('empty', '')
            ->orderBy('t.id', 'ASC');

        // SELECT * FROM `tracks` WHERE `spotify_isrc` IS NULL AND `spotify_is_deleted` = 0 ORDER BY `tracks`.`id` ASC

        $spotifyTrackIds = [];

        /** @var Track[] $tracks */
        $tracks = $queryBuilder->getQuery()->getResult();

        foreach ($tracks as $track) {
            $spotifyTrackIds[] = $track->getSpotifyId();
        }

        $items = $this->spotifyGrab->getTrackByIds($spotifyTrackIds);

        $spotifyTrackIdsUpdated = [];
        $i = 0;

        foreach ($items as $item) {
            $this->spotifyTrackCreator->handle(
                new Spotify\RefreshTrack\Command(
                    albumId: null,
                    trackId: $item->id,
                    trackLinkedId: $item->linkedId,
                    diskNumber: $item->discNumber,
                    trackNumber: $item->trackNumber,
                    name: $item->name,
                    explicit: $item->explicit,
                    artists: $item->artists,
                    isrc: $item->isrc,
                    duration: $item->duration,
                    type: $item->type,
                    isLocal: $item->isLocal,
                    availableMarkets: $item->availableMarkets
                )
            );

            $spotifyTrackIdsUpdated[] = $item->id;

            $output->writeln('<info>' . (++$i) . ') ' . $item->name . ' | ' . $item->id . ' - Spotify track metadata fixes!</info>');
        }
        $output->writeln('<info>Spotify tracks fixes! Count: ' . \count($spotifyTrackIdsUpdated) . ' / ' . \count($tracks) . '</info>');
    }

    private function fixesSpotifyUPC(OutputInterface $output): void
    {
        $this->em->clear();

        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('a')
            ->from(Album::class, 'a')
            ->andWhere('a.spotifyUPC IS NULL AND a.spotifyExternalIds IS NOT NULL')
            ->orderBy('a.id', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(25000);

        /** @var Album[] $albums */
        $albums = $queryBuilder->getQuery()->getResult();

        $i = 0;

        foreach ($albums as $album) {
            $upc = $album->getSpotifyUpcFromExternalIds();

            $album->setSpotifyUPC($upc);

            $output->writeln('<info>' . ++$i . ') ' . $album->getSpotifyName() . ' - ' . ($album->getSpotifyUPC() ?? '-') . '</info>');

            $this->em->flush();
        }

        $output->writeln('<info>Spotify UPC fixes!</info>');
    }

    private function setTidalSettings(): void
    {
        $token = $this->tidalTokenRepository->findFirstActive(TidalToken::TYPE_API);

        if (null === $token) {
            throw new Exception('NO TIDAL ACCESS TOKEN');
        }

        $this->tidalGrab->setAccessToken($token->getAccessToken());
    }

    /** @throws Exception */
    private function fixesTidalAlbums(OutputInterface $output): void
    {
        $this->em->clear();

        $sql = 'UPDATE tidal_albums SET properties = NULL WHERE properties = "[]"';
        $this->em->getConnection()->executeQuery($sql);

        $sql = 'UPDATE tidal_albums SET images = NULL WHERE images = "[]"';
        $this->em->getConnection()->executeQuery($sql);

        $sql = 'UPDATE tidal_albums SET videos = NULL WHERE videos = "[]"';
        $this->em->getConnection()->executeQuery($sql);

        $output->writeln('<info>Tidal albums metadata fixes!</info>');

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select('ta')
            ->from(TidalAlbum::class, 'ta')
            ->andWhere('ta.type = :empty OR ta.name = :empty OR ta.images = :empty OR ta.mediaMetadata IS NULL')
            ->andWhere('ta.isDeleted = false')
            ->setParameter('empty', '')
            ->orderBy('ta.id', 'ASC');

        $tidalAlbumIds = [];

        /** @var TidalAlbum[] $tidalAlbums */
        $tidalAlbums = $queryBuilder->getQuery()->getResult();

        foreach ($tidalAlbums as $tidalAlbum) {
            $tidalAlbumIds[] = $tidalAlbum->getTidalId();
        }

        $items = $this->tidalGrab->getAlbumByIds($tidalAlbumIds);

        $tidalAlbumIdsUpdated = [];
        $i = 0;

        foreach ($items as $item) {
            $this->tidalAlbumCreator->handle(
                new Tidal\RefreshAlbum\Command(
                    artistId: null,
                    albumId: $item->id,
                    type: $item->type,
                    barcodeId: $item->barcodeId,
                    name: $item->title,
                    artists: $item->artists,
                    images: $item->imageCovers,
                    videos: $item->videoCovers,
                    releasedAt: $item->release,
                    totalTracks: $item->totalTracks,
                    copyrights: $item->copyright,
                    mediaMetadata: $item->mediaMetadata,
                    properties: $item->properties,
                )
            );

            $tidalAlbumIdsUpdated[] = $item->id;

            $output->writeln('<info>' . (++$i) . ') ' . $item->title . ' - Tidal album metadata fixes!</info>');
        }

        $notFoundTidalIds = array_diff($tidalAlbumIds, $tidalAlbumIdsUpdated);

        if (\count($notFoundTidalIds) > 0) {
            $sql = 'UPDATE tidal_albums SET is_deleted = 1 WHERE tidal_id IN(' . implode(',', $notFoundTidalIds) . ')';
            $this->em->getConnection()->executeQuery($sql);

            $output->writeln('<error>' . \count($notFoundTidalIds) . ' - Albums not found!</error>');
        }

        $sql = 'UPDATE albums SET all_tracks_mapped = 0 WHERE tidal_album_id IN(SELECT id FROM tidal_albums WHERE is_deleted = 1)';
        $this->em->getConnection()->executeQuery($sql);
    }

    /** @throws Exception */
    private function fixesTidalTracks(OutputInterface $output): void
    {
        $this->em->clear();

        $sql = 'UPDATE tidal_tracks SET properties = NULL WHERE properties = "[]"';
        $this->em->getConnection()->executeQuery($sql);

        $output->writeln('<info>Tidal tracks metadata fixes!</info>');

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select('tt')
            ->from(TidalTrack::class, 'tt')
            ->andWhere('tt.name = :empty OR tt.isrc = :empty OR tt.mediaMetadata IS NULL')
            ->andWhere('tt.isDeleted = false')
            ->setParameter('empty', '')
            ->orderBy('tt.id', 'ASC');

        $tidalTrackIds = [];

        /** @var TidalTrack[] $tidalTracks */
        $tidalTracks = $queryBuilder->getQuery()->getResult();

        foreach ($tidalTracks as $tidalTrack) {
            $tidalTrackIds[] = (string)$tidalTrack->getTidalId();
        }

        $items = $this->tidalGrab->getTrackByIds($tidalTrackIds);

        $tidalTrackIdsUpdated = [];
        $i = 0;

        foreach ($items as $item) {
            $this->tidalTrackCreator->handle(
                new Tidal\RefreshTrack\Command(
                    albumId: null,
                    trackId: $item->id,
                    diskNumber: $item->discNumber,
                    trackNumber: $item->trackNumber,
                    isrc: $item->isrc,
                    name: $item->name,
                    artists: $item->artists,
                    explicit: $item->explicit,
                    duration: $item->duration,
                    version: $item->version,
                    copyright: $item->copyright,
                    mediaMetadata: $item->mediaMetadata,
                    properties: $item->properties,
                    popularity: $item->popularity,
                    attributes: $item->artists,
                )
            );

            $tidalTrackIdsUpdated[] = $item->id;

            $output->writeln('<info>' . (++$i) . ') ' . $item->name . ' - Tidal track metadata fixes!</info>');
        }

        $notFoundTidalIds = array_diff($tidalTrackIds, $tidalTrackIdsUpdated);

        if (\count($notFoundTidalIds) > 0) {
            $sql = 'UPDATE tidal_tracks SET is_deleted = 1 WHERE tidal_id IN(' . implode(',', $notFoundTidalIds) . ')';
            $this->em->getConnection()->executeQuery($sql);

            $output->writeln('<error>' . \count($notFoundTidalIds) . ' - Tracks not found!</error>');
        }
    }

    private function setAppleSettings(): void
    {
        $this->appleGrab->setCountryCode('US');
        $this->appleGrab->setDelay(0);
    }

    /** @throws Exception */
    private function fixesAppleAlbums(OutputInterface $output): void
    {
        $this->em->clear();

        $sql = 'UPDATE apple_albums SET genre_names = NULL WHERE genre_names = "[]"';
        $this->em->getConnection()->executeQuery($sql);

        $output->writeln('<info>Apple albums metadata fixes!</info>');

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select('aa')
            ->from(AppleAlbum::class, 'aa')
            ->andWhere('aa.images IS NULL')
            ->andWhere('aa.isDeleted = false')
            ->orderBy('aa.id', 'ASC');

        /** @var AppleAlbum[] $appleAlbums */
        $appleAlbums = $queryBuilder->getQuery()->getResult();

        $i = 0;

        foreach ($appleAlbums as $appleAlbum) {
            /**
             * @var array{
             *    artwork: array{
             *        width: int,
             *        height: int,
             *        url: string,
             *    }
             * }|null $attributes
             */
            $attributes = $appleAlbum->getAttributes();

            if (null !== $attributes) {
                $image = $attributes['artwork']['url'];
                $image = str_replace('{w}', (string)$attributes['artwork']['width'], $image);
                $image = str_replace('{h}', (string)$attributes['artwork']['height'], $image);

                $appleAlbum->setImages($image);
            }

            $output->writeln('<info>' . (++$i) . ') ' . $appleAlbum->getName() . ' - Apple album metadata fixes!</info>');
        }

        $this->em->flush();
    }

    private function setTidalDLToken(int $tokenId): void
    {
        while (true) {
            $accessToken = $this->tidalTokenRepository->findById($tokenId);

            if (
                null === $accessToken ||
                $accessToken->getType() !== TidalToken::TYPE_DL ||
                !$accessToken->isActive()
            ) {
                echo PHP_EOL . 'NO ACCESS TOKENS!' . PHP_EOL;
                sleep(Constant::SLEEP_NO_ACCESS_TOKEN);

                continue;
            }

            $this->tidalDL->setAccessToken($accessToken->getAccessToken());
            return;
        }
    }

    /** @throws Throwable */
    private function tryLoadTidalNotFoundTracks(OutputInterface $output): void
    {
        // https://tidal.com/browse/track/158672715
        $this->em->clear();

        $sql = '
            SELECT
                albums.id,
                albums.spotify_name
            FROM
                albums
            WHERE
                all_tracks_mapped = 0 AND
                lo_album_id IS NOT NULL AND
                is_reissued = 0 AND
                id IN(
                    SELECT
                        tracks.album_id
                    FROM
                        tracks INNER JOIN tidal_tracks ON tracks.tidal_track_id = tidal_tracks.id
                    WHERE
                        tracks.lo_track_id IS NULL AND
                        tidal_tracks.is_deleted = 1
                )
            ORDER BY albums.id DESC;
        ';

        /**
         * @var array{id: int, spotify_name: string}[] $rows
         */
        $rows = $this->em->getConnection()
            ->executeQuery($sql)
            ->fetchAllAssociative();

        foreach ($rows as $row) {
            try {
                $album = $this->albumRepository->getById($row['id']);

                $output->writeln('<info>Try load album: ' . $album->getId() . ' - ' . $album->getSpotifyName() . ', Artists: ' . ($album->getSeparateArtists() ?? '-') . '</info>');

                $albumArtists = $this->getAlbumArtistsFetcher->fetch(
                    new GetAlbumArtists\Query($album->getId())
                );

                foreach ($albumArtists as $albumArtist) {
                    $artist = $this->artistRepository->getById($albumArtist->getArtistId());

                    $this->albumLoader->handle($artist, $album, $output);
                }
            } catch (Throwable $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        }

        $output->writeln('<info>Total: ' . \count($rows) . '</info>');
    }

    private function normalizeDeletedTracks(OutputInterface $output): void
    {
        $this->em->clear();

        $sql = '
            UPDATE
                tidal_tracks
            SET
                is_deleted = 0
            WHERE
                id IN(
                    SELECT
                        tracks.tidal_track_id
                    FROM
                        tracks INNER JOIN tidal_tracks ON tracks.tidal_track_id = tidal_tracks.id
                    WHERE
                        tracks.lo_track_id IS NOT NULL AND
                        tidal_tracks.is_deleted = 1
                )
        ';
        $this->em->getConnection()->executeQuery($sql);

        $output->writeln('<info>Tidal deleted tracks normalize!</info>');
    }
}
