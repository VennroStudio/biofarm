<?php

declare(strict_types=1);

namespace App\Modules\Command\Apple\AddAlbumArtist;

use App\Modules\Entity\AppleAlbumArtist\AppleAlbumArtist;
use App\Modules\Entity\AppleAlbumArtist\AppleAlbumArtistRepository;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private AppleAlbumArtistRepository $appleAlbumArtistRepository,
        private Flusher $flusher,
    ) {}

    public function handle(Command $command): AppleAlbumArtist
    {
        $albumArtist = $this->appleAlbumArtistRepository->findByAlbumAndArtistIds($command->albumId, $command->artistId);

        if (null !== $albumArtist) {
            return $albumArtist;
        }

        $albumArtist = AppleAlbumArtist::create(
            albumId: $command->albumId,
            artistId: $command->artistId,
        );

        $this->appleAlbumArtistRepository->add($albumArtist);
        $this->flusher->flush();

        return $albumArtist;
    }
}
