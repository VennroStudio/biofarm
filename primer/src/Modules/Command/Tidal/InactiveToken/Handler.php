<?php

declare(strict_types=1);

namespace App\Modules\Command\Tidal\InactiveToken;

use App\Modules\Entity\TidalToken\TidalTokenRepository;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private TidalTokenRepository $tidalTokenRepository,
        private Flusher $flusher,
    ) {}

    public function handle(Command $command): void
    {
        $tidalToken = $this->tidalTokenRepository->findById($command->id);

        if (null === $tidalToken) {
            return;
        }

        $error = mb_substr($command->error, 0, 10000);

        $tidalToken->setStatusOff();
        $tidalToken->setErrorMessage($error);

        $this->tidalTokenRepository->add($tidalToken);
        $this->flusher->flush();
    }
}
