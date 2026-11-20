<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Integration;

use App\Components\Http\Response\JsonDataResponse;
use App\Components\Integration\Bitrix24\Bitrix24CrmSettings;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetBitrix24SettingsAction implements RequestHandlerInterface
{
    public function __construct(
        private Bitrix24CrmSettings $settings,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonDataResponse($this->settings->publicState());
    }
}
