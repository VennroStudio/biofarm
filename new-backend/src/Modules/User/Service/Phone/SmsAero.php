<?php

declare(strict_types=1);

namespace App\Modules\User\Service\Phone;

use App\Components\Exception\DomainExceptionModule;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;

final readonly class SmsAero
{
    public const int CONFIRMED = 1;
    public const int DECLINED = 2;
    public const int CODE_REQUIRED = 3;
    public const int FAILED = 16;
    public const string UNAVAILABLE_MESSAGE = 'Подтверждение телефона по СМС временно недоступно.';

    public function __construct(
        private ClientInterface $client,
        private string $email,
        private string $key,
        private string $sign,
        private string $callbackUrl,
    ) {}

    public function configured(): bool
    {
        return trim($this->email) !== '' && trim($this->key) !== '' && trim($this->sign) !== ''
            && strcasecmp(trim($this->sign), 'SMS Aero') !== 0
            && str_starts_with($this->callbackUrl, 'https://');
    }

    public function start(string $phone): array
    {
        return $this->call('send', ['number' => $phone, 'sign' => $this->sign, 'callbackUrl' => $this->callbackUrl]);
    }

    public function status(int $id, string $phone): int
    {
        return $this->assertSession($this->call('status', ['id' => $id]), $id, $phone);
    }

    public function verify(int $id, string $phone, string $code): int
    {
        return $this->assertSession($this->call('verify', ['id' => $id, 'sign' => $this->sign, 'code' => $code]), $id, $phone);
    }

    private function assertSession(array $session, int $id, string $phone): int
    {
        if ($session['id'] !== $id || $session['number'] !== $phone) {
            throw $this->unavailable();
        }

        return $session['status'];
    }

    private function call(string $action, array $payload): array
    {
        if (!$this->configured()) {
            throw $this->unavailable();
        }

        try {
            $response = $this->client->request('POST', 'https://gate.smsaero.ru/v2/mobile-id/' . $action, [
                'auth' => [$this->email, $this->key],
                'json' => $payload,
                'http_errors' => false,
                'allow_redirects' => false,
                'connect_timeout' => 3,
                'timeout' => 8,
            ]);
            $result = json_decode((string)$response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        } catch (GuzzleException | JsonException) {
            throw $this->unavailable();
        }

        if ($action === 'verify' && $response->getStatusCode() === 400
            && strtolower(rtrim((string)($result['message'] ?? ''), '.')) === 'invalid otp code') {
            throw new DomainExceptionModule('phone', 'Неверный код подтверждения.', 72, status: 422);
        }

        $data = $result['data'] ?? null;
        if ($response->getStatusCode() !== 200 || ($result['success'] ?? false) !== true || !is_array($data)
            || !is_int($data['id'] ?? null) || (int)$data['id'] < 1 || !is_int($data['status'] ?? null)
            || PhoneNumber::normalize((string)($data['number'] ?? '')) === null) {
            throw $this->unavailable();
        }

        return ['id' => $data['id'], 'status' => $data['status'], 'number' => PhoneNumber::normalize((string)$data['number'])];
    }

    private function unavailable(): DomainExceptionModule
    {
        return new DomainExceptionModule('phone', self::UNAVAILABLE_MESSAGE, 73, status: 503);
    }
}
