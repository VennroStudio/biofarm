<?php

declare(strict_types=1);

namespace App\Modules\Command\ArtistSocial\Delete;

use App\Modules\Command\Artist\UpdateStatsSocials;
use App\Modules\Entity\ArtistSocial\ArtistSocialRepository;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private ArtistSocialRepository $artistSocialRepository,
        private UpdateStatsSocials\Handler $updateStatsSocials,
        private Flusher $flusher,
    ) {}

    public function handle(Command $command): void
    {
        $artistSocial = $this->artistSocialRepository->findById($command->socialId);

        if (null === $artistSocial) {
            return;
        }

        $artistSocial->setDeletedAt(time());

        $this->artistSocialRepository->add($artistSocial);
        $this->flusher->flush();

        $this->updateStatsSocials->handle($artistSocial->getArtistId());
    }
}
