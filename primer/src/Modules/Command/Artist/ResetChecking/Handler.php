<?php

declare(strict_types=1);

namespace App\Modules\Command\Artist\ResetChecking;

use App\Modules\Entity\Artist\ArtistRepository;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private ArtistRepository $artistRepository,
        private Flusher $flusher,
    ) {}

    public function handle(Command $command): void
    {
        $artist = $this->artistRepository->getById($command->artistId);

        $artist->resetChecking();

        $this->artistRepository->add($artist);
        $this->flusher->flush();
    }
}
