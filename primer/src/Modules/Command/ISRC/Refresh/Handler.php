<?php

declare(strict_types=1);

namespace App\Modules\Command\ISRC\Refresh;

use App\Modules\Entity\ISRC\ISRC;
use App\Modules\Entity\ISRC\ISRCRepository;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private ISRCRepository $ISRCRepository,
        private Flusher $flusher,
    ) {}

    public function handle(Command $command): ISRC
    {
        $isrc = $this->ISRCRepository->findByISRCId($command->id);

        if (null === $isrc) {
            $isrc = ISRC::create(
                isrcId: $command->id,
                duration: $command->duration,
                recordingVersion: $command->recordingVersion,
                recordingType: $command->recordingType,
                recordingYear: $command->recordingYear,
                recordingArtistName: $command->recordingArtistName,
                isExplicit: $command->isExplicit,
                releaseLabel: $command->releaseLabel,
                icpn: $command->icpn,
                releaseDate: $command->releaseDate,
                genre: $command->genre,
                releaseName: $command->releaseName,
                releaseArtistName: $command->releaseArtistName,
                recordingTitle: $command->recordingTitle
            );
        }

        $isrc->edit(
            duration: $command->duration,
            recordingVersion: $command->recordingVersion,
            recordingType: $command->recordingType,
            recordingYear: $command->recordingYear,
            recordingArtistName: $command->recordingArtistName,
            releaseLabel: $command->releaseLabel,
            icpn: $command->icpn,
            releaseDate: $command->releaseDate,
            genre: $command->genre,
            releaseName: $command->releaseName,
            releaseArtistName: $command->releaseArtistName,
            recordingTitle: $command->recordingTitle
        );

        $this->ISRCRepository->add($isrc);
        $this->flusher->flush();

        return $isrc;
    }
}
