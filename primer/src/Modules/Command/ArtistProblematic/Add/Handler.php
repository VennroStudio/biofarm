<?php

declare(strict_types=1);

namespace App\Modules\Command\ArtistProblematic\Add;

use App\Modules\Entity\ArtistProblematic\ArtistProblematic;
use App\Modules\Entity\ArtistProblematic\ArtistProblematicRepository;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private ArtistProblematicRepository $artistProblematicRepository,
        private Flusher $flusher,
    ) {}

    public function handle(Command $command): ArtistProblematic
    {
        $existing = $this->artistProblematicRepository->findByArtistId($command->artistId);

        if (null !== $existing) {
            return $existing;
        }

        $artistProblematic = ArtistProblematic::create(
            artistId: $command->artistId,
            artistName: $command->artistName,
        );

        $this->artistProblematicRepository->add($artistProblematic);
        $this->flusher->flush();

        return $artistProblematic;
    }
}
