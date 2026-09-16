<?php

declare(strict_types=1);

namespace App\Modules\Payment\Service;

use App\Components\Exception\DomainExceptionModule;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;

use function App\Components\env;

final class YooKassaGateway
{
    private ClientInterface $http;
    private array $config;

    public function __construct(?ClientInterface $http = null, ?array $config = null)
    {
        $this->http = $http ?? new Client();
        $this->config = $config ?? [
            'shopId'     => env('YOOKASSA_SHOP_ID', ''),
            'secret'     => env('YOOKASSA_SECRET_KEY', ''),
            'returnBase' => rtrim(env('YOOKASSA_RETURN_BASE_URL', ''), '/'),
            'receipts'   => env('YOOKASSA_RECEIPTS_ENABLED', '0') === '1',
            'vatCode'    => (int)env('YOOKASSA_VAT_CODE', '1'),
            'taxSystem'  => (int)env('YOOKASSA_TAX_SYSTEM_CODE', '0'),
        ];
    }

    public function configured(): bool
    {
        return ($this->config['shopId'] ?? '') !== '' && ($this->config['secret'] ?? '') !== ''
            && preg_match('~^https?://[^/]+~', $this->config['returnBase'] ?? '') === 1;
    }

    public function publicConfig(): array
    {
        return ['configured' => $this->configured(), 'receiptsEnabled' => (bool)($this->config['receipts'] ?? false)];
    }

    public function returnUrl(string $orderId): string
    {
        return ($this->config['returnBase'] ?? '') . '/order-success?order=' . rawurlencode($orderId);
    }

    public function createPayment(array $payload, string $key): array
    {
        return $this->request('POST', 'payments', $payload, $key);
    }

    public function payment(string $id): array
    {
        return $this->request('GET', 'payments/' . rawurlencode($id));
    }

    public function createRefund(array $payload, string $key): array
    {
        return $this->request('POST', 'refunds', $payload, $key);
    }

    public function refund(string $id): array
    {
        return $this->request('GET', 'refunds/' . rawurlencode($id));
    }

    public function createReceipt(array $payload, string $key): array
    {
        return $this->request('POST', 'receipts', $payload, $key);
    }

    public function receiptStatus(string $id): array
    {
        return $this->request('GET', 'receipts/' . rawurlencode($id));
    }

    public function receipt(array $order, array $items, string $paymentMode = 'full_prepayment'): ?array
    {
        if (!($this->config['receipts'] ?? false)) {
            return null;
        }
        $shipping = \is_array($order['shipping_address']) ? $order['shipping_address'] : json_decode($order['shipping_address'], true, 512, JSON_THROW_ON_ERROR);
        $customer = [];
        if (filter_var($shipping['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $customer['email'] = $shipping['email'];
        } elseif (preg_match('/^\+?\d{10,15}$/', preg_replace('/[^+\d]/', '', $shipping['phone'] ?? ''))) {
            $customer['phone'] = preg_replace('/[^+\d]/', '', $shipping['phone']);
        }
        if ($customer === []) {
            throw new DomainExceptionModule('payment', 'Для чека нужен корректный email или телефон.', 1, status: 422);
        }
        $lines = [];
        foreach ($items as $item) {
            // Receipts use individually allocated paid units to preserve exact totals after discounts.
            if ((int)$item['amountMinor'] <= 0) {
                continue;
            }
            $lines[] = [
                'description'     => mb_substr((string)$item['description'], 0, 128),
                'quantity'        => (string)($item['quantity'] ?? 1),
                'amount'          => ['value' => self::money((int)$item['amountMinor']), 'currency' => 'RUB'],
                'vat_code'        => (int)($this->config['vatCode'] ?? 1),
                'payment_mode'    => $paymentMode,
                'payment_subject' => ($item['delivery'] ?? false) ? 'service' : 'commodity',
            ];
        }
        $receipt = ['customer' => $customer, 'items' => $lines];
        if (($this->config['taxSystem'] ?? 0) > 0) {
            $receipt['tax_system_code'] = (int)$this->config['taxSystem'];
        }
        return $receipt;
    }

    public static function money(int $minor): string
    {
        if ($minor < 0) {
            throw new InvalidArgumentException('Negative payment');
        }
        return intdiv($minor, 100) . '.' . str_pad((string)($minor % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function minor(string $value): int
    {
        if (!preg_match('/^(\d{1,10})\.(\d{2})$/D', $value, $matches)) {
            throw new InvalidArgumentException('Invalid payment amount');
        }
        return (int)$matches[1] * 100 + (int)$matches[2];
    }

    private function request(string $method, string $path, ?array $payload = null, ?string $key = null): array
    {
        if (!$this->configured()) {
            throw new DomainExceptionModule('payment', 'Онлайн-оплата пока не настроена. Обратитесь в магазин.', 2, status: 503);
        }
        $options = ['auth' => [$this->config['shopId'], $this->config['secret']], 'timeout' => 20, 'connect_timeout' => 5, 'allow_redirects' => false, 'http_errors' => false, 'headers' => ['Accept' => 'application/json']];
        if ($payload !== null) {
            $options['json'] = $payload;
        }
        if ($key !== null) {
            $options['headers']['Idempotence-Key'] = $key;
        }
        try {
            $response = $this->http->request($method, 'https://api.yookassa.ru/v3/' . $path, $options);
        } catch (GuzzleException) {
            throw new DomainExceptionModule('payment', 'Платёжный сервис временно недоступен. Статус операции будет проверен повторно.', 3, status: 503);
        }
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            // Never echo provider body: it may contain integration secrets or customer data.
            throw new DomainExceptionModule('payment', 'Платёжный сервис не подтвердил операцию. Повторите проверку позже.', 4, status: 503);
        }
        $result = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        if (!\is_array($result) || !\is_string($result['id'] ?? null) || !\is_string($result['status'] ?? null)) {
            throw new DomainExceptionModule('payment', 'Некорректный ответ платёжного сервиса.', 5, status: 503);
        }
        return $result;
    }
}
