<?php

declare(strict_types=1);

namespace App\Modules\Command\PlaylistTranslate\Delete;

use App\Modules\Command\PlaylistTranslate\HelperTranslateLO;
use App\Modules\Entity\Playlist\PlaylistRepository;
use App\Modules\Entity\PlaylistTranslate\PlaylistTranslateRepository;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private PlaylistTranslateRepository $playlistTranslateRepository,
        private PlaylistRepository $playlistRepository,
        private HelperTranslateLO $helperTranslateLO,
        private Flusher $flusher,
    ) {}

    public function handle(Command $command): void
    {
        $playlist = $this->playlistRepository->getById($command->playlistId);
        $translate = $this->playlistTranslateRepository->findById($command->translateId);

        if (null === $translate) {
            return;
        }

        $this->playlistTranslateRepository->remove($translate);
        $this->flusher->flush();

        $this->helperTranslateLO->deleteFromLO($playlist, $translate->getLang());
    }
}
