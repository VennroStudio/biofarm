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
        private Bitrix24CrmGateway $crm,
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
            'title'               => 'Заявка с формы: ' . $feedback['name'],
            'opportunity'         => 0,
            'currencyId'          => 'RUB',
            'isManualOpportunity' => 'Y',
            'comments'            => $this->comments($feedback),
            'sourceId'            => 'WEB',
            'sourceDescription'   => 'Форма на сайте БИОФАРМ',
            'opened'              => 'Y',
        ];

        $contactId = $this->createContactId($webhookUrl, $feedbackId, $feedback);

        if ($contactId !== null) {
            $fields['contactIds'] = [$contactId];
        }

        try {
            $dealId = $this->crm->addDeal($webhookUrl, $fields);
            if ($dealId !== null) {
                $this->connection->update('feedback_requests', ['bitrix_deal_id' => $dealId], ['id' => $feedbackId]);
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
    private function createContactId(string $webhookUrl, int $feedbackId, array $feedback): ?int
    {
        try {
            return $this->crm->addContact(
                $webhookUrl,
                $feedback['name'],
                $feedback['phone'],
                $feedback['email'],
            );
        } catch (Bitrix24Exception $exception) {
            $this->errorLogger->log(
                service: 'bitrix24',
                scenario: 'feedback_form',
                operation: 'crm.item.add.contact',
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
                operation: 'crm.item.add.contact',
                message: $exception->getMessage(),
                localEntityType: 'feedback_request',
                localEntityId: (string)$feedbackId,
            );
        }

        return null;
    }

    /**
     * @param array{name: string, phone: string|null, email: string|null, message: string, source_page: string|null} $feedback
     */
    private function comments(array $feedback): string
    {
        $lines = [
            'Имя: ' . $feedback['name'],
            'Телефон: ' . ($feedback['phone'] ?? '-'),
            'Email: ' . ($feedback['email'] ?? '-'),
            'Сообщение: ' . $feedback['message'],
        ];

        if ($feedback['source_page'] !== null) {
            $lines[] = 'Страница: ' . $feedback['source_page'];
        }

        return implode("\n", $lines);
    }
}
