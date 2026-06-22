<?php

declare(strict_types=1);

namespace App\Http\Web\System;

use App\Components\App\AppInfo;
use App\Components\Http\Response\JsonResponseFactory;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class HealthController implements RequestHandlerInterface
{
    public function __construct(
        private AppInfo $app,
        private JsonResponseFactory $json,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->json->create([
            'status'  => 'ok',
            'service' => $this->app->name,
            'env'     => $this->app->environment,
            'version' => $this->app->version,
        ]);
    }
}
