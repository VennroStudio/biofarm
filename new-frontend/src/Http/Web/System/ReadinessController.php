<?php

declare(strict_types=1);

namespace App\Http\Web\System;

use App\Components\Asset\ViteManifest;
use App\Components\Http\Response\JsonResponseFactory;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final readonly class ReadinessController implements RequestHandlerInterface
{
    public function __construct(
        private ViteManifest $assets,
        private JsonResponseFactory $json,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->assets->asset('assets/styles/app.css');
            $this->assets->asset('assets/react/mount.tsx');
        } catch (Throwable $exception) {
            return $this->json->create([
                'status' => 'not_ready',
                'checks' => [
                    'assets' => $exception->getMessage(),
                ],
            ], 503);
        }

        return $this->json->create([
            'status' => 'ready',
            'checks' => [
                'assets' => 'ok',
            ],
        ]);
    }
}
