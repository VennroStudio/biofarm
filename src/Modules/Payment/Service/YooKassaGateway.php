<?php

declare(strict_types=1);

namespace App\Modules\Payment\Service;

use App\Components\Exception\DomainExceptionModule;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;

use function App\Components\env;
use function App\Components\env_bool;
use function App\Components\env_int;

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
            'returnBase' => rtrim(env('FRONTEND_URL', '') ?: env('APP_URL', ''), '/'),
            'returnPath' => trim(env('YOOKASSA_RETURN_BASE_URL', '')) ?: '/order-success',
            'receipts'   => env('YOOKASSA_RECEIPTS_ENABLED', '0') === '1',
            'vatCode'    => (int)env('YOOKASSA_VAT_CODE', '1'),
            'taxSystem'  => (int)env('YOOKASSA_TAX_SYSTEM_CODE', '0'),
            'ffd12'      => env_bool('YOOKASSA_FFD12_ENABLED', false),
            'timezone'   => env_int('YOOKASSA_RECEIPT_TIMEZONE', 0),
        ];
    }

    public function configured(): bool
    {
        $base = $this->config['returnBase'] ?? '';
        $url = parse_url($base);
        return ($this->config['shopId'] ?? '') !== '' && ($this->config['secret'] ?? '') !== ''
            && filter_var($base, FILTER_VALIDATE_URL) !== false && $url !== false
            && (($url['scheme'] ?? '') === 'https' || (($url['scheme'] ?? '') === 'http' && env('APP_ENV', 'prod') === 'dev'))
            && !isset($url['user']) && !isset($url['pass']) && !isset($url['query']) && !isset($url['fragment'])
            && \in_array($url['path'] ?? '', ['', '/'], true)
            && preg_match('~^/?[a-zA-Z0-9][a-zA-Z0-9/_.-]*$~D', $this->config['returnPath'] ?? '/order-success') === 1;
    }

    public function publicConfig(): array
    {
        return ['configured' => $this->configured(), 'receiptsEnabled' => (bool)($this->config['receipts'] ?? false)];
    }

    public function markingConfigured(): bool
    {
        return ($this->config['ffd12'] ?? false) === true
            && (int)($this->config['timezone'] ?? 0) >= 1 && (int)($this->config['timezone'] ?? 0) <= 11;
    }

    public function returnUrl(string $orderId): string
    {
        return rtrim($this->config['returnBase'] ?? '', '/') . '/'
            . ltrim($this->config['returnPath'] ?? '/order-success', '/')
            . '?order=' . rawurlencode($orderId);
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
            $line = [
                'description'     => mb_substr((string)$item['description'], 0, 128),
                'quantity'        => (string)($item['quantity'] ?? 1),
                'amount'          => ['value' => self::money((int)$item['amountMinor']), 'currency' => 'RUB'],
                'vat_code'        => (int)($this->config['vatCode'] ?? 1),
                'payment_mode'    => $paymentMode,
                'payment_subject' => isset($item['mark']) ? 'marked' : (($item['delivery'] ?? false) ? 'service' : 'commodity'),
            ];
            if ($this->markingConfigured()) {
                $line['measure'] = ($item['delivery'] ?? false) ? 'another' : 'piece';
            }
            if (isset($item['mark'])) {
                if (!$this->markingConfigured() || (int)$line['quantity'] !== 1) {
                    throw new DomainExceptionModule('payment', 'Чек маркированного товара требует кассу ФФД 1.2 и отдельную строку на экземпляр.', 35);
                }
                $line['mark_code_info'] = ['gs_1m' => self::base64Mark($item['mark'])];
                $line['mark_mode'] = '0';
                $line['mark_quantity'] = ['numerator' => 1, 'denominator' => 1];
            }
            $lines[] = $line;
        }
        $receipt = ['customer' => $customer, 'items' => $lines];
        if ($this->markingConfigured()) {
            $receipt += ['internet' => true, 'timezone' => $this->config['timezone']];
        }
        if (($this->config['taxSystem'] ?? 0) > 0) {
            $receipt['tax_system_code'] = (int)$this->config['taxSystem'];
        }
        return $receipt;
    }

    public static function base64Mark(string $mark): string
    {
        $decoded = base64_decode($mark, true);
        if ($decoded !== false && base64_encode($decoded) === $mark && preg_match('/^(?:\x1d|\]d2)?01[0-9]{14}21/s', $decoded)) {
            return $mark;
        }
        if (!preg_match('/^(?:\x1d|\]d2)?01[0-9]{14}21/s', $mark)) {
            throw new DomainExceptionModule('payment', 'Некорректный формат кода маркировки Ozon.', 35);
        }
        return base64_encode($mark);
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
        $test = $result['test'] ?? null;
        $isPayment = $path === 'payments' || str_starts_with($path, 'payments/');
        if (env('APP_ENV', 'prod') !== 'dev' && ($test === true || ($isPayment && $test !== false))) {
            throw new DomainExceptionModule('payment', 'ЮKassa не подтвердила боевой режим оплаты.', 6, status: 503);
        }
        return $result;
    }
}
