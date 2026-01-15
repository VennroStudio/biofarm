<?php

declare(strict_types=1);

namespace App\Modules\Command\SpotifyToken\Refresh;

use App\Modules\Entity\SpotifyToken\SpotifyTokenRepository;
use Exception;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private SpotifyTokenRepository $spotifyTokenRepository,
        private Flusher $flusher,
    ) {}

    /** @throws Exception */
    public function handle(Command $command): void
    {
        $spotifyToken = $this->spotifyTokenRepository->getById($command->id);

        $spotifyToken->refresh($command->accessToken);

        $this->spotifyTokenRepository->add($spotifyToken);
        $this->flusher->flush();
    }
}
