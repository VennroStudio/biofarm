<?php

declare(strict_types=1);

namespace App\Components\Integration\Bitrix24;

use App\Components\Integration\IntegrationErrorLogger;
use Doctrine\DBAL\Connection;
use Throwable;

final readonly class Bitrix24FeedbackSyncer
{
    public function __construct(
        private Bitrix24CrmSettings $settings,
        private Bitrix24Client $client,
        private IntegrationErrorLogger $errorLogger,
        private Connection $connection,
    ) {}

    /**
     * @param array{name: string, phone: string|null, email: string|null, message: string, source_page: string|null} $feedback
     */
    public function submitted(int $feedbackId, array $feedback): void
    {
        if (!$this->settings->ready()) {
            return;
        }

        $webhookUrl = $this->settings->webhookUrl();
        if ($webhookUrl === null) {
            return;
        }

        $fields = [
            'title'             => 'Заявка с формы обратной связи',
            'name'              => $feedback['name'],
            'comments'          => $this->comments($feedback),
            'sourceId'          => 'WEB',
            'sourceDescription' => 'Форма на сайте БИОФАРМ',
            'fm'                => [],
        ];

        if ($feedback['phone'] !== null) {
            $fields['fm'][] = [
                'typeId'    => 'PHONE',
                'valueType' => 'WORK',
                'value'     => $feedback['phone'],
            ];
        }

        if ($feedback['email'] !== null) {
            $fields['fm'][] = [
                'typeId'    => 'EMAIL',
                'valueType' => 'WORK',
                'value'     => $feedback['email'],
            ];
        }

        try {
            $response = $this->client->call($webhookUrl, 'crm.item.add', [
                'entityTypeId' => 1,
                'fields'       => $fields,
            ]);

            $leadId = isset($response['result']['item']['id']) ? (int)$response['result']['item']['id'] : null;
            if ($leadId !== null && $leadId > 0) {
                $this->connection->update('feedback_requests', ['bitrix_lead_id' => $leadId], ['id' => $feedbackId]);
            }
        } catch (Bitrix24Exception $exception) {
            $this->errorLogger->log(
                service: 'bitrix24',
                scenario: 'feedback_form',
                operation: 'crm.item.add',
                message: $exception->getMessage(),
                httpStatus: $exception->httpStatus(),
                responseBody: $exception->responseBody(),
                localEntityType: 'feedback_request',
                localEntityId: (string)$feedbackId,
            );
        } catch (Throwable $exception) {
            $this->errorLogger->log(
                service: 'bitrix24',
                scenario: 'feedback_form',
                operation: 'crm.item.add',
                message: $exception->getMessage(),
                localEntityType: 'feedback_request',
                localEntityId: (string)$feedbackId,
            );
        }
    }

    /**
     * @param array{name: string, phone: string|null, email: string|null, message: string, source_page: string|null} $feedback
     */
    private function comments(array $feedback): string
    {
        $lines = [
            'Сообщение: ' . $feedback['message'],
        ];

        if ($feedback['source_page'] !== null) {
            $lines[] = 'Страница: ' . $feedback['source_page'];
        }

        return implode("\n", $lines);
    }
}
