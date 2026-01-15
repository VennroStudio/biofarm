<?php

declare(strict_types=1);

namespace App\Modules\Command\PlaylistTranslate\Update;

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
        $translate = $this->playlistTranslateRepository->getById($command->translateId);
        $playlist = $this->playlistRepository->getById($command->playlistId);

        $photoHost = null;
        $photoFileId = null;

        $filePath = null !== $command->filePath ? trim($command->filePath) : null;

        if ($filePath === '') {
            $filePath = null;
        }

        if (null !== $filePath) {
            $photoData = $this->helperTranslateLO->photoData($playlist->getId(), $filePath);
            if (null !== $photoData) {
                $photoHost = $photoData['host'];
                $photoFileId = $photoData['file_id'];
            }
        }

        $translate->edit(
            name: $command->name,
            description: $command->description,
            photoHost: null !== $photoHost ? $photoHost : $translate->getPhotoHost(),
            photoFileId: null !== $photoFileId ? $photoFileId : $translate->getPhotoFileId(),
        );

        $this->playlistTranslateRepository->add($translate);
        $this->flusher->flush();

        $this->helperTranslateLO->saveToLO($playlist, $translate);
    }
}
