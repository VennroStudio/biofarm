<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Integration;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Integration\Bitrix24\Bitrix24CrmSettings;
use App\Components\Integration\Bitrix24\Bitrix24WebhookValidator;
use Doctrine\DBAL\Exception;
use JsonException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class UpdateBitrix24SettingsAction implements RequestHandlerInterface
{
    public function __construct(
        private Bitrix24CrmSettings $settings,
        private Bitrix24WebhookValidator $validator,
    ) {}

    /**
     * @throws Exception
     * @throws JsonException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $payload = (array)$request->getParsedBody();
        $enabled = (bool)($payload['enabled'] ?? false);
        $webhookUrl = trim((string)($payload['webhook_url'] ?? ''));
        $webhookUrl = $webhookUrl !== '' ? rtrim($webhookUrl, '/') : null;

        if ($webhookUrl !== null && !$this->validator->valid($webhookUrl)) {
            throw new DomainExceptionModule('integration', 'error.bitrix_webhook_invalid', 1, status: 422);
        }

        if ($enabled && $webhookUrl === null && !$this->settings->publicState()['has_webhook']) {
            throw new DomainExceptionModule('integration', 'error.bitrix_webhook_required', 2, status: 422);
        }

        $this->settings->update($enabled, $webhookUrl);

        return new JsonDataResponse($this->settings->publicState());
    }
}
