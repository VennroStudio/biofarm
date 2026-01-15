<?php

declare(strict_types=1);

namespace App\Modules\Command\TrackProblematic\UpdateStatus;

use App\Modules\Entity\TrackProblematic\TrackProblematicRepository;
use DomainException;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private TrackProblematicRepository $trackProblematicRepository,
        private Flusher $flusher,
    ) {}

    public function handle(Command $command): void
    {
        /** @var array<int> $ids */
        $ids = \is_array($command->loTrackId) ? $command->loTrackId : [$command->loTrackId];

        foreach ($ids as $loTrackId) {
            $trackProblematic = $this->trackProblematicRepository->findByLoTrackId($loTrackId);

            if ($trackProblematic === null) {
                throw new DomainException('Track not found');
            }

            $trackProblematic->updateStatus($command->status);
        }

        $this->flusher->flush();
    }
}
