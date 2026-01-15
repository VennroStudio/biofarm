<?php

declare(strict_types=1);

namespace App\Modules\Command\PlaylistTranslate\Create;

use App\Modules\Command\PlaylistTranslate\HelperTranslateLO;
use App\Modules\Entity\Playlist\PlaylistRepository;
use App\Modules\Entity\PlaylistTranslate\PlaylistTranslate;
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

    public function handle(Command $command): PlaylistTranslate
    {
        $translate = $this->playlistTranslateRepository->findByLang($command->playlistId, $command->lang);

        if (null !== $translate) {
            return $translate;
        }

        $playlist = $this->playlistRepository->getById($command->playlistId);

        $photoHost = null;
        $photoFileId = null;

        $filePath = null !== $command->filePath ? trim($command->filePath) : null;

        if ($filePath === '') {
            $filePath = null;
        }

        if (null !== $filePath) {
            $photoData = $this->helperTranslateLO->photoData($command->playlistId, $filePath);
            if (null !== $photoData) {
                $photoHost = $photoData['host'];
                $photoFileId = $photoData['file_id'];
            }
        }

        $translate = PlaylistTranslate::create(
            playlistId: $playlist->getId(),
            lang: $command->lang,
            name: $command->name,
            description: $command->description,
            photoHost: $photoHost,
            photoFileId: $photoFileId
        );

        $this->playlistTranslateRepository->add($translate);
        $this->flusher->flush();

        $this->helperTranslateLO->saveToLO($playlist, $translate);

        return $translate;
    }
}
