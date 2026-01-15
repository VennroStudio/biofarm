<?php

declare(strict_types=1);

namespace App\Modules\Command\Apple\RefreshAlbum;

use App\Modules\Command\Apple\AddAlbumArtist;
use App\Modules\Entity\AppleAlbum\AppleAlbum;
use App\Modules\Entity\AppleAlbum\AppleAlbumRepository;
use App\Modules\Entity\AppleAlbumArtist\AppleAlbumArtistRepository;
use Exception;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private AppleAlbumRepository $appleAlbumRepository,
        private AppleAlbumArtistRepository $appleAlbumArtistRepository,
        private AddAlbumArtist\Handler $addArtistHandler,
        private Flusher $flusher,
    ) {}

    /** @throws Exception */
    public function handle(Command $command): AppleAlbum
    {
        $appleAlbum = $this->appleAlbumRepository->findByAppleId($command->albumId);

        if (null === $appleAlbum) {
            if (null === $command->artistId) {
                throw new Exception('Missing artist id');
            }

            $appleAlbum = AppleAlbum::create(
                artistId: $command->artistId,
                appleId: $command->albumId,
                upc: $command->upc,
                name: $command->name,
                isCompilation: $command->isCompilation,
                isSingle: $command->isSingle,
                releasedAt: $command->releasedAt,
                totalTracks: $command->totalTracks,
                artists: $command->artists,
                image: $command->image,
                video: $command->video,
                copyrights: $command->copyrights,
                label: $command->label,
                genreNames: $command->genreNames,
                attributes: $command->attributes,
            );
        }

        $appleAlbum->edit(
            upc: $command->upc,
            name: $command->name,
            isCompilation: $command->isCompilation,
            isSingle: $command->isSingle,
            releasedAt: $command->releasedAt,
            totalTracks: $command->totalTracks,
            artists: $command->artists,
            image: $command->image,
            video: $command->video,
            copyrights: $command->copyrights,
            label: $command->label,
            genreNames: $command->genreNames,
            attributes: $command->attributes,
        );

        $appleAlbum->setUpdatedAt(time());

        $this->appleAlbumRepository->add($appleAlbum);
        $this->flusher->flush();

        if (null !== $command->artistId) {
            $this->checkArtist($appleAlbum, $command->artistId);
        }

        return $appleAlbum;
    }

    private function checkArtist(AppleAlbum $appleAlbum, int $artistId): void
    {
        $albumArtist = $this->appleAlbumArtistRepository->findByAlbumAndArtistIds($appleAlbum->getId(), $artistId);

        if (null !== $albumArtist) {
            return;
        }

        $this->addArtistHandler->handle(
            new AddAlbumArtist\Command(
                albumId: $appleAlbum->getId(),
                artistId: $artistId,
            )
        );
    }
}
