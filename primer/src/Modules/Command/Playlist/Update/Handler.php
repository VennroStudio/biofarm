<?php

declare(strict_types=1);

namespace App\Modules\Command\Playlist\Update;

use App\Modules\Entity\Playlist\PlaylistRepository;
use Throwable;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private PlaylistRepository $playlistRepository,
        private Flusher $flusher,
    ) {}

    /** @throws Throwable */
    public function handle(Command $command): void
    {
        $playlist = $this->playlistRepository->getById($command->playlistId);

        $playlist->edit(
            name: $command->name,
            isFollowed: $command->isFollowed
        );

        $this->flusher->flush();
    }
}
