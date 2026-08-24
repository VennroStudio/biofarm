<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Setting;

use App\Components\Http\Response\JsonDataResponse;
use App\Components\Setting\SiteSettings;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetSettingsAction implements RequestHandlerInterface
{
    public function __construct(
        private SiteSettings $siteSettings,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonDataResponse($this->siteSettings->all());
    }
}
