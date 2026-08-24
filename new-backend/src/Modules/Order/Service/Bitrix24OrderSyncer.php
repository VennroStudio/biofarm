<?php

declare(strict_types=1);

namespace App\Modules\Order\Service;

use App\Components\Integration\Bitrix24\Bitrix24CrmSettings;
use App\Components\Integration\Bitrix24\Bitrix24CrmGateway;
use App\Components\Integration\Bitrix24\Bitrix24Exception;
use App\Components\Integration\IntegrationErrorLogger;
use App\Modules\Order\Entity\Order\Order;
use Doctrine\DBAL\Connection;
use Throwable;

final readonly class Bitrix24OrderSyncer
{
    public function __construct(
        private Bitrix24CrmSettings $settings,
        private Bitrix24CrmGateway $crm,
        private IntegrationErrorLogger $errorLogger,
        private Connection $connection,
    ) {}

    /**
     * @param list<array{product_id: int, product_name: string, price: int, quantity: int}> $items
     */
    public function created(Order $order, array $items): void
    {
        if (!$this->settings->ready()) {
            return;
        }

        $webhookUrl = $this->settings->webhookUrl();
        if ($webhookUrl === null) {
            return;
        }

        $contactId = $this->createContactId($webhookUrl, $order);

        $fields = [
            'title'               => 'Заказ ' . $order->id . ' с сайта',
            'opportunity'         => $order->total,
            'currencyId'          => 'RUB',
            'isManualOpportunity' => 'Y',
            'comments'            => $this->comments($order, $items),
            'sourceId'            => 'WEB',
            'sourceDescription'   => 'Сайт BioFarm',
            'opened'              => 'Y',
        ];

        if ($contactId !== null) {
            $fields['contactIds'] = [$contactId];
        }

        try {
            $dealId = $this->crm->addDeal($webhookUrl, $fields);
            if ($dealId === null || $dealId <= 0) {
                return;
            }

            $this->connection->update('orders', ['bitrix_deal_id' => $dealId], ['id' => $order->id]);
            $this->attachProductRows($webhookUrl, $dealId, $order, $items);
        } catch (Bitrix24Exception $exception) {
            $this->log($order->id, 'crm.item.add', $exception);
        } catch (Throwable $exception) {
            $this->errorLogger->log(
                service: 'bitrix24',
                scenario: 'order_created',
                operation: 'crm.item.add',
                message: $exception->getMessage(),
                localEntityType: 'order',
                localEntityId: $order->id,
            );
        }
    }

    private function createContactId(string $webhookUrl, Order $order): ?int
    {
        try {
            return $this->crm->addContact(
                $webhookUrl,
                trim((string)($order->shippingAddress['name'] ?? '')),
                trim((string)($order->shippingAddress['phone'] ?? '')) ?: null,
                trim((string)($order->shippingAddress['email'] ?? '')) ?: null,
            );
        } catch (Bitrix24Exception $exception) {
            $this->log($order->id, 'crm.item.add.contact', $exception);
        } catch (Throwable $exception) {
            $this->errorLogger->log(
                service: 'bitrix24',
                scenario: 'order_created',
                operation: 'crm.item.add.contact',
                message: $exception->getMessage(),
                localEntityType: 'order',
                localEntityId: $order->id,
            );
        }

        return null;
    }

    /**
     * @param list<array{product_id: int, product_name: string, price: int, quantity: int}> $items
     */
    private function attachProductRows(string $webhookUrl, int $dealId, Order $order, array $items): void
    {
        if ($items === []) {
            return;
        }

        $rows = array_map(static fn (array $item): array => [
            'productName' => $item['product_name'],
            'price'       => $item['price'],
            'quantity'    => $item['quantity'],
        ], $items);

        try {
            $this->crm->setDealProductRows($webhookUrl, $dealId, $rows);
        } catch (Bitrix24Exception $exception) {
            $this->log($order->id, 'crm.item.productrow.set', $exception);
        } catch (Throwable $exception) {
            $this->errorLogger->log(
                service: 'bitrix24',
                scenario: 'order_created',
                operation: 'crm.item.productrow.set',
                message: $exception->getMessage(),
                localEntityType: 'order',
                localEntityId: $order->id,
                context: ['bitrix_deal_id' => $dealId],
            );
        }
    }

    /**
     * @param list<array{product_id: int, product_name: string, price: int, quantity: int}> $items
     */
    private function comments(Order $order, array $items): string
    {
        $address = $order->shippingAddress;
        $lines = [
            'Заказ: ' . $order->id,
            'Клиент: ' . trim((string)($address['name'] ?? '')),
            'Телефон: ' . trim((string)($address['phone'] ?? '')),
            'Email: ' . trim((string)($address['email'] ?? '')),
            'Город: ' . trim((string)($address['city'] ?? '')),
            'Адрес: ' . trim((string)($address['address'] ?? '')),
            'Доставка: ' . (string)$order->deliveryMethod,
            'Оплата: ' . $order->paymentMethod,
            'Итого: ' . $order->total . ' ₽',
            '',
            'Товары:',
        ];

        foreach ($items as $item) {
            $lines[] = \sprintf(
                '- %s, %d шт. × %d ₽',
                $item['product_name'],
                $item['quantity'],
                $item['price'],
            );
        }

        return implode("\n", array_filter($lines, static fn (string $line): bool => trim($line) !== ''));
    }

    private function log(string $orderId, string $operation, Bitrix24Exception $exception): void
    {
        $this->errorLogger->log(
            service: 'bitrix24',
            scenario: 'order_created',
            operation: $operation,
            message: $exception->getMessage(),
            httpStatus: $exception->httpStatus(),
            responseBody: $exception->responseBody(),
            localEntityType: 'order',
            localEntityId: $orderId,
        );
    }
}
