<?php

declare(strict_types=1);

namespace App\Console\Spotify;

use App\Components\SpotifyGrab\SpotifyGrab;
use App\Modules\Command\Artist\UpdateStatsAlbums;
use App\Modules\Command\Spotify\InactiveToken;
use App\Modules\Command\Spotify\RefreshAlbum;
use App\Modules\Constant;
use App\Modules\Entity\Album\Album;
use App\Modules\Entity\AlbumArtist\AlbumArtist;
use App\Modules\Entity\Artist\ArtistRepository;
use App\Modules\Entity\ArtistSocial\ArtistSocial;
use App\Modules\Query\Artists\GetArtistSocials;
use App\Modules\Query\GetSpotifyToken;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use Throwable;

class SpotifyAlbumsParser
{
    private ?string $accessToken = null;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly GetArtistSocials\Fetcher $socialsFetcher,
        private readonly ArtistRepository $artistRepository,
        private readonly SpotifyGrab $spotifyGrab,
        private readonly RefreshAlbum\Handler $spotifyCreator,
        private readonly SpotifyTracksParser $spotifyTracksParser,
        private readonly GetSpotifyToken\Fetcher $spotifyTokenFetcher,
        private readonly InactiveToken\Handler $inactiveTokenHandler,
        private readonly UpdateStatsAlbums\SpotifyHandler $updateStatsAlbums,
    ) {}

    /** @throws Throwable */
    public function handle(int $artistId, bool $isFullScan, ?int $tokenId): void
    {
        $socials = $this->socialsFetcher->fetch(
            new GetArtistSocials\Query(
                artisId: $artistId,
                type: ArtistSocial::TYPE_SPOTIFY
            )
        );

        $spotifyArtistIds = [];

        foreach ($socials as $social) {
            $spotifyArtistIds[] = $social->getIdByUrl();
        }

        $types = ['album', 'single', 'appears_on'];

        try {
            foreach ($socials as $social) {
                foreach ($types as $type) {
                    $lastAlbumIds = $isFullScan ? null : $this->getLastAlbumIds($artistId, $type);

                    $this->parseSocial($social, $type, $spotifyArtistIds, $lastAlbumIds, $tokenId);
                }
            }
        } catch (Throwable $e) {
            $this->inactiveAccessToken($e->getMessage());
            echo PHP_EOL . $e->getMessage() . PHP_EOL;
            return;
        }

        $artist = $this->artistRepository->getById($artistId);
        $artist->setSpotifyChecked();

        $this->em->flush();
    }

    /** @return string[] */
    private function getLastAlbumIds(int $artistId, string $type): array
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('album')
            ->from(AlbumArtist::class, 'aa')
            ->innerJoin(Album::class, 'album', Join::WITH, 'aa.albumId = album.id AND aa.artistId = :artistId')
            ->where('album.spotifyType = :type')
            ->setParameter('artistId', $artistId)
            ->setParameter('type', $type);

        $queryBuilder
            ->orderBy('album.spotifyReleasedAt', 'DESC')
            ->addOrderBy('aa.id', 'ASC')
            ->setMaxResults(50);

        /** @var Album[] $albums */
        $albums = $queryBuilder->getQuery()->getResult();

        $items = [];

        foreach ($albums as $album) {
            $items[] = (string)$album->getSpotifyId();
        }

        return array_unique($items);
    }

    /**
     * @param string[] $spotifyArtistIds
     * @param string[]|null $lastAlbumIds
     * @throws Exception
     */
    private function parseSocial(ArtistSocial $social, string $type, array $spotifyArtistIds, ?array $lastAlbumIds, ?int $tokenId): void
    {
        $maxCount = null !== $lastAlbumIds ? 10 : null;

        $this->refreshAccessToken($tokenId);
        $spotifyAlbums = $this->spotifyGrab->getAlbums($social->getIdByUrl(), $type, $maxCount);

        foreach ($spotifyAlbums as $spotifyAlbum) {
            if (null !== $lastAlbumIds && \in_array($spotifyAlbum->id, $lastAlbumIds, true)) {
                break;
            }

            $this->refreshAccessToken($tokenId);
            $album = $this->spotifyCreator->handle(
                new RefreshAlbum\Command(
                    artistId: $social->getArtistId(),
                    spotifyArtistIds: $spotifyArtistIds,
                    albumId: $spotifyAlbum->id,
                    type: $spotifyAlbum->type,
                    name: $spotifyAlbum->name,
                    releasedAt: $spotifyAlbum->release,
                    releasedPrecision: $spotifyAlbum->releasePrecision,
                    totalTracks: $spotifyAlbum->totalTracks,
                    availableMarkets: $spotifyAlbum->availableMarkets,
                    artists: $spotifyAlbum->artists,
                    upc: $spotifyAlbum->upc,
                    images: $spotifyAlbum->images,
                    copyrights: $spotifyAlbum->copyrights,
                    externalIds: $spotifyAlbum->externalIds,
                    genres: $spotifyAlbum->genres,
                    label: $spotifyAlbum->label,
                    popularity: $spotifyAlbum->popularity
                )
            );

            $this->refreshAccessToken($tokenId);
            $this->spotifyTracksParser->handle($album);

            $this->em->clear();
        }

        $this->updateStatsAlbums->handle($social->getArtistId());
    }

    private function refreshAccessToken(?int $tokenId): void
    {
        $this->accessToken = $this->getAccessToken($tokenId);
        $this->spotifyGrab->setAccessToken($this->accessToken);
    }

    private function getAccessToken(?int $tokenId): string
    {
        while (true) {
            $accessToken = $this->spotifyTokenFetcher->fetch($tokenId);

            if (null !== $accessToken) {
                return $accessToken;
            }

            echo PHP_EOL . 'NO ACCESS TOKENS!' . PHP_EOL;
            sleep(Constant::SLEEP_NO_ACCESS_TOKEN);
        }
    }

    private function inactiveAccessToken(string $error): void
    {
        if (null === $this->accessToken) {
            return;
        }

        $this->inactiveTokenHandler->handle(
            new InactiveToken\Command(
                accessToken: $this->accessToken,
                error: $error
            )
        );
    }
}
