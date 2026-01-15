<?php

declare(strict_types=1);

namespace App\Modules\Command\Playlist\ResetChecking;

use App\Modules\Entity\Playlist\PlaylistRepository;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private PlaylistRepository $playlistRepository,
        private Flusher $flusher,
    ) {}

    public function handle(Command $command): void
    {
        $playlist = $this->playlistRepository->getById($command->playlistId);

        $playlist->resetChecking();

        $this->playlistRepository->add($playlist);
        $this->flusher->flush();
    }
}
