<?php

declare(strict_types=1);

namespace App\Modules\Command\Album\AddArtist;

use App\Modules\Entity\AlbumArtist\AlbumArtist;
use App\Modules\Entity\AlbumArtist\AlbumArtistRepository;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private AlbumArtistRepository $albumArtistRepository,
        private Flusher $flusher,
    ) {}

    public function handle(Command $command): AlbumArtist
    {
        $albumArtist = $this->albumArtistRepository->findByAlbumAndArtistIds($command->albumId, $command->artistId);

        if (null !== $albumArtist) {
            return $albumArtist;
        }

        $albumArtist = AlbumArtist::create(
            albumId: $command->albumId,
            artistId: $command->artistId,
            number: $command->number
        );

        $this->albumArtistRepository->add($albumArtist);
        $this->flusher->flush();

        return $albumArtist;
    }
}
