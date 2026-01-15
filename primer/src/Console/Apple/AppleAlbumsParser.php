<?php

declare(strict_types=1);

namespace App\Console\Apple;

use App\Components\AppleGrab\AppleGrab;
use App\Modules\Command\Apple\RefreshAlbum;
use App\Modules\Command\Artist\UpdateStatsAlbums;
use App\Modules\Entity\AppleAlbum\AppleAlbum;
use App\Modules\Entity\Artist\Artist;
use App\Modules\Entity\Artist\ArtistRepository;
use App\Modules\Entity\ArtistSocial\ArtistSocial;
use App\Modules\Query\Artists\GetArtistSocials;
use App\Modules\Query\Artists\GetArtistSocials\Query;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Throwable;

readonly class AppleAlbumsParser
{
    public function __construct(
        private EntityManagerInterface $em,
        private GetArtistSocials\Fetcher $socialsFetcher,
        private ArtistRepository $artistRepository,
        private AppleGrab $appleGrab,
        private RefreshAlbum\Handler $appleCreator,
        private AppleTracksParser $appleTracksParser,
        private UpdateStatsAlbums\AppleHandler $updateStatsAlbums,
    ) {}

    /** @throws Throwable */
    public function handle(Artist $artist, bool $isFullScan): void
    {
        $lastAlbumIds = $isFullScan ? null : $this->getLastAlbumIds($artist->getId());

        $socials = $this->socialsFetcher->fetch(
            new Query(
                artisId: $artist->getId(),
                type: ArtistSocial::TYPE_APPLE
            )
        );

        foreach ($socials as $social) {
            $this->parseSocial($social, $lastAlbumIds);
        }

        $artist->setAppleChecked();
        $this->artistRepository->add($artist);

        $this->em->flush();
    }

    /** @return string[] */
    private function getLastAlbumIds(int $artistId): array
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('aa')
            ->from(AppleAlbum::class, 'aa')
            ->where('aa.artistId = :artistId')
            ->orderBy('aa.releasedAt', 'DESC')
            ->addOrderBy('aa.id', 'ASC')
            ->setMaxResults(50)
            ->setParameter('artistId', $artistId);

        /** @var AppleAlbum[] $albums */
        $albums = $queryBuilder->getQuery()->getResult();

        $items = [];

        foreach ($albums as $album) {
            $items[] = $album->getAppleId();
        }

        return array_unique($items);
    }

    /**
     * @param string[]|null $lastAlbumIds
     * @throws Exception
     */
    private function parseSocial(ArtistSocial $social, ?array $lastAlbumIds): void
    {
        $maxCount = null !== $lastAlbumIds ? 25 : null;
        $appleAlbums = $this->appleGrab->getAlbums($social->getIdByUrl(), $maxCount);

        foreach ($appleAlbums as $appleAlbum) {
            if (null !== $lastAlbumIds && \in_array($appleAlbum->id, $lastAlbumIds, true)) {
                break;
            }

            $album = $this->appleCreator->handle(
                new RefreshAlbum\Command(
                    artistId: $social->getArtistId(),
                    albumId: $appleAlbum->id,
                    upc: $appleAlbum->upc,
                    name: $appleAlbum->name,
                    isCompilation: $appleAlbum->isCompilation,
                    isSingle: $appleAlbum->isSingle,
                    releasedAt: $appleAlbum->releaseAt,
                    totalTracks: $appleAlbum->totalTracks,
                    artists: $appleAlbum->artistsString,
                    image: $appleAlbum->imageCover,
                    video: $appleAlbum->videoCover,
                    copyrights: $appleAlbum->copyright,
                    label: $appleAlbum->label,
                    genreNames: $appleAlbum->genreNames,
                    attributes: $appleAlbum->attributes,
                )
            );

            $this->appleTracksParser->handle($album);
        }

        $this->updateStatsAlbums->handle($social->getArtistId());
    }
}
