<?php

declare(strict_types=1);

namespace App\Modules\Command\ArtistProblematic\UpdateStatus;

use App\Modules\Entity\ArtistProblematic\ArtistProblematicRepository;
use Throwable;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private ArtistProblematicRepository $artistProblematicRepository,
        private Flusher $flusher,
    ) {}

    /** @throws Throwable */
    public function handle(Command $command): void
    {
        $artistIds = \is_array($command->artistId) ? $command->artistId : [$command->artistId];

        foreach ($artistIds as $artistId) {
            $artistProblematic = $this->artistProblematicRepository->findByArtistId($artistId);

            if (null === $artistProblematic) {
                continue;
            }

            $artistProblematic->updateStatus($command->status);
        }

        $this->flusher->flush();
    }
}
