<?php

declare(strict_types=1);

namespace App\Modules\Command\Tidal\RefreshAlbum;

use App\Modules\Command\Tidal\AddAlbumArtist;
use App\Modules\Entity\TidalAlbum\TidalAlbum;
use App\Modules\Entity\TidalAlbum\TidalAlbumRepository;
use App\Modules\Entity\TidalAlbumArtist\TidalAlbumArtistRepository;
use Exception;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private TidalAlbumRepository $tidalAlbumRepository,
        private TidalAlbumArtistRepository $tidalAlbumArtistRepository,
        private AddAlbumArtist\Handler $addArtistHandler,
        private Flusher $flusher,
    ) {}

    /** @throws Exception */
    public function handle(Command $command): TidalAlbum
    {
        $tidalAlbum = $this->tidalAlbumRepository->findByTidalId($command->albumId);

        if (null === $tidalAlbum) {
            if (null === $command->artistId) {
                throw new Exception('Missing artist id');
            }

            $tidalAlbum = TidalAlbum::create(
                artistId: $command->artistId,
                tidalId: $command->albumId,
                type: $command->type,
                barcodeId: $command->barcodeId,
                name: $command->name,
                artists: $command->artists,
                images: $command->images,
                videos: $command->videos,
                releasedAt: $command->releasedAt,
                totalTracks: $command->totalTracks,
                copyrights: $command->copyrights,
                mediaMetadata: $command->mediaMetadata,
                properties: $command->properties
            );
        }

        $tidalAlbum->edit(
            type: $command->type,
            barcodeId: $command->barcodeId,
            name: $command->name,
            artists: $command->artists,
            images: $command->images,
            videos: $command->videos,
            releasedAt: $command->releasedAt,
            totalTracks: $command->totalTracks,
            copyrights: $command->copyrights,
            mediaMetadata: $command->mediaMetadata,
            properties: $command->properties,
        );

        $tidalAlbum->setUpdatedAt(time());

        $this->tidalAlbumRepository->add($tidalAlbum);
        $this->flusher->flush();

        if (null !== $command->artistId) {
            $this->checkArtist($tidalAlbum, $command->artistId);
        }

        return $tidalAlbum;
    }

    private function checkArtist(TidalAlbum $tidalAlbum, int $artistId): void
    {
        $albumArtist = $this->tidalAlbumArtistRepository->findByAlbumAndArtistIds($tidalAlbum->getId(), $artistId);

        if (null !== $albumArtist) {
            return;
        }

        $this->addArtistHandler->handle(
            new AddAlbumArtist\Command(
                albumId: $tidalAlbum->getId(),
                artistId: $artistId,
            )
        );
    }
}
