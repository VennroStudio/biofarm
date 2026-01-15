<?php

declare(strict_types=1);

namespace App\Modules\Command\Spotify\InactiveToken;

use App\Modules\Entity\SpotifyToken\SpotifyTokenRepository;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private SpotifyTokenRepository $spotifyTokenRepository,
        private Flusher $flusher,
    ) {}

    public function handle(Command $command): void
    {
        $spotifyToken = $this->spotifyTokenRepository->findByAccessToken($command->accessToken);

        if (null === $spotifyToken) {
            return;
        }

        $spotifyToken->setStatusOff();
        $spotifyToken->setErrorMessage($command->error);

        $this->spotifyTokenRepository->add($spotifyToken);
        $this->flusher->flush();
    }
}
