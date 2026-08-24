<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Integration;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Integration\Bitrix24\Bitrix24Client;
use App\Components\Integration\Bitrix24\Bitrix24CrmSettings;
use App\Components\Integration\Bitrix24\Bitrix24Exception;
use App\Components\Integration\IntegrationErrorLogger;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class TestBitrix24ConnectionAction implements RequestHandlerInterface
{
    public function __construct(
        private Bitrix24CrmSettings $settings,
        private Bitrix24Client $client,
        private IntegrationErrorLogger $errorLogger,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $webhookUrl = $this->settings->webhookUrl();
        if ($webhookUrl === null) {
            throw new DomainExceptionModule('integration', 'error.bitrix_webhook_required', 1, status: 422);
        }

        try {
            $this->client->call($webhookUrl, 'crm.lead.fields');
        } catch (Bitrix24Exception $exception) {
            $this->errorLogger->log(
                service: 'bitrix24',
                scenario: 'admin_test',
                operation: 'crm.lead.fields',
                message: $exception->getMessage(),
                httpStatus: $exception->httpStatus(),
                responseBody: $exception->responseBody(),
            );
            throw new DomainExceptionModule('integration', 'error.bitrix_connection_failed', 2, status: 502);
        }

        return new JsonDataResponse(['ok' => true]);
    }
}
