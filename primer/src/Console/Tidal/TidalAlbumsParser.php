<?php

declare(strict_types=1);

namespace App\Console\Tidal;

use App\Components\TidalGrab\TidalGrab;
use App\Modules\Command\Artist\UpdateStatsAlbums;
use App\Modules\Command\ArtistProblematic\Add\Command;
use App\Modules\Command\ArtistProblematic\Add\Handler as ArtistProblematicAddHandler;
use App\Modules\Command\Tidal\RefreshAlbum;
use App\Modules\Entity\Artist\ArtistRepository;
use App\Modules\Entity\ArtistSocial\ArtistSocial;
use App\Modules\Entity\TidalAlbum\TidalAlbum;
use App\Modules\Query\Artists\GetArtistSocials;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Throwable;

readonly class TidalAlbumsParser
{
    public function __construct(
        private EntityManagerInterface $em,
        private GetArtistSocials\Fetcher $socialsFetcher,
        private ArtistRepository $artistRepository,
        private TidalGrab $tidalGrab,
        private RefreshAlbum\Handler $tidalCreator,
        private TidalTracksParser $tidalTracksParser,
        private UpdateStatsAlbums\TidalHandler $updateStatsAlbums,
        private ArtistProblematicAddHandler $artistProblematicAddHandler,
    ) {}

    /** @throws Throwable */
    public function handle(int $artistId, bool $isFullScan): void
    {
        $lastAlbumIds = $isFullScan ? null : $this->getLastAlbumIds($artistId);

        $socials = $this->socialsFetcher->fetch(
            new GetArtistSocials\Query(
                artisId: $artistId,
                type: ArtistSocial::TYPE_TIDAL
            )
        );

        foreach ($socials as $social) {
            $this->parseSocial($social, $lastAlbumIds);
        }

        $artist = $this->artistRepository->getById($artistId);

        $artist->setTidalChecked();
        $this->artistRepository->add($artist);

        $this->em->flush();
    }

    /** @return string[] */
    private function getLastAlbumIds(int $artistId): array
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('ta')
            ->from(TidalAlbum::class, 'ta')
            ->where('ta.artistId = :artistId')
            ->orderBy('ta.releasedAt', 'DESC')
            ->addOrderBy('ta.id', 'ASC')
            ->setMaxResults(50)
            ->setParameter('artistId', $artistId);

        /** @var TidalAlbum[] $albums */
        $albums = $queryBuilder->getQuery()->getResult();

        $items = [];

        foreach ($albums as $album) {
            $items[] = $album->getTidalId();
        }

        return array_unique($items);
    }

    /**
     * @param string[]|null $lastAlbumIds
     * @throws Exception
     */
    private function parseSocial(ArtistSocial $social, ?array $lastAlbumIds): void
    {
        $maxCount = null !== $lastAlbumIds ? 20 : null;
        $tidalAlbums = $this->tidalGrab->getAlbums($social->getIdByUrl(), $maxCount);

        // echo PHP_EOL . 'COUNT ALBUMS: ' . \count($tidalAlbums) . PHP_EOL; // todo: DELETE

        foreach ($tidalAlbums as $tidalAlbum) {
            if (null !== $lastAlbumIds && \in_array($tidalAlbum->id, $lastAlbumIds, true)) {
                break;
            }

            // echo '[' . $k . '] ALBUM ID: ' . $tidalAlbum->id . PHP_EOL; // todo: DELETE

            $this->em->clear();

            $album = $this->tidalCreator->handle(
                new RefreshAlbum\Command(
                    artistId: $social->getArtistId(),
                    albumId: $tidalAlbum->id,
                    type: $tidalAlbum->type,
                    barcodeId: $tidalAlbum->barcodeId,
                    name: $tidalAlbum->title,
                    artists: $tidalAlbum->artists,
                    images: $tidalAlbum->imageCovers,
                    videos: $tidalAlbum->videoCovers,
                    releasedAt: $tidalAlbum->release,
                    totalTracks: $tidalAlbum->totalTracks,
                    copyrights: $tidalAlbum->copyright,
                    mediaMetadata: $tidalAlbum->mediaMetadata,
                    properties: $tidalAlbum->properties,
                )
            );

            $countAttempts = 0;

            while (true) {
                try {
                    $this->tidalTracksParser->handle($album);
                    break;
                } catch (Throwable $e) {
                    echo PHP_EOL . $album->getTidalId() . ' - ' . $e->getMessage() . PHP_EOL;

                    $this->handleArtistNotFoundError($e->getMessage(), $social->getArtistId());

                    ++$countAttempts;

                    if ($countAttempts >= 20) {
                        throw $e;
                    }

                    sleep(30);
                }
            }
        }

        $this->updateStatsAlbums->handle($social->getArtistId());
    }

    private function handleArtistNotFoundError(string $errorMessage, int $artistId): void
    {
        if (preg_match('/NOT_FOUND:\s*Artist of a given \'id\'\s+(\d+)\s+not found/i', $errorMessage)) {
            try {
                $artist = $this->artistRepository->getById($artistId);
                $this->artistProblematicAddHandler->handle(
                    new Command(
                        artistId: $artistId,
                        artistName: $artist->getLoName(),
                    )
                );
            } catch (Throwable) {
            }
        }
    }
}
