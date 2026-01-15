<?php

declare(strict_types=1);

namespace App\Modules\Command\Playlist\AddTrack;

use App\Modules\Entity\PlaylistTrack\PlaylistTrack;
use App\Modules\Entity\PlaylistTrack\PlaylistTrackRepository;
use Exception;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private PlaylistTrackRepository $playlistTrackRepository,
        private Flusher $flusher,
    ) {}

    /** @throws Exception */
    public function handle(Command $command): PlaylistTrack
    {
        $track = $this->playlistTrackRepository->findByPlaylistAndTrack($command->playlistId, $command->trackId);

        if (null !== $track) {
            return $track;
        }

        $track = PlaylistTrack::create(
            playlistId: $command->playlistId,
            trackId: $command->trackId,
            number: $command->number,
        );

        $this->playlistTrackRepository->add($track);
        $this->flusher->flush();

        return $track;
    }
}
