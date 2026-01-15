<?php

declare(strict_types=1);

namespace App\Modules\Command\ArtistSocial\Create;

use App\Modules\Command\Artist\UpdateStatsSocials;
use App\Modules\Entity\ArtistSocial\ArtistSocial;
use App\Modules\Entity\ArtistSocial\ArtistSocialRepository;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private ArtistSocialRepository $artistSocialRepository,
        private UpdateStatsSocials\Handler $updateStatsSocials,
        private Flusher $flusher,
    ) {}

    public function handle(Command $command): ArtistSocial
    {
        $artistSocial = $this->artistSocialRepository->findByUrl($command->artistId, $command->url);

        if (null !== $artistSocial) {
            return $artistSocial;
        }

        $artistSocial = ArtistSocial::create(
            artistId: $command->artistId,
            url: $command->url,
            description: $command->description
        );

        $this->artistSocialRepository->add($artistSocial);
        $this->flusher->flush();

        $this->updateStatsSocials->handle($artistSocial->getArtistId());

        return $artistSocial;
    }
}
