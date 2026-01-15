<?php

declare(strict_types=1);

namespace App\Modules\Command\Tidal\AddAlbumArtist;

use App\Modules\Entity\TidalAlbumArtist\TidalAlbumArtist;
use App\Modules\Entity\TidalAlbumArtist\TidalAlbumArtistRepository;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private TidalAlbumArtistRepository $tidalAlbumArtistRepository,
        private Flusher $flusher,
    ) {}

    public function handle(Command $command): TidalAlbumArtist
    {
        $albumArtist = $this->tidalAlbumArtistRepository->findByAlbumAndArtistIds($command->albumId, $command->artistId);

        if (null !== $albumArtist) {
            return $albumArtist;
        }

        $albumArtist = TidalAlbumArtist::create(
            albumId: $command->albumId,
            artistId: $command->artistId,
        );

        $this->tidalAlbumArtistRepository->add($albumArtist);
        $this->flusher->flush();

        return $albumArtist;
    }
}
