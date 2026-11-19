<?php

declare(strict_types=1);

namespace App\Modules\Ozon;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;

final readonly class OzonApi
{
    public function __construct(private ClientInterface $http) {}

    public function seller(string $path, string $token, array $payload): array
    {
        return $this->post('https://api-seller.ozon.ru' . $path, [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'json' => $payload === [] ? (object)[] : $payload,
        ], $path);
    }

    public function oauth(array $parameters): array
    {
        return $this->post('https://xapi.ozon.ru/oauth/token', [
            'form_params' => $parameters,
        ], 'OAuth');
    }

    private function post(string $url, array $options, string $operation): array
    {
        try {
            $response = $this->http->request('POST', $url, $options + [
                'http_errors' => false,
                'allow_redirects' => false,
                'connect_timeout' => 4,
                'timeout' => 15,
                'headers' => ['Accept' => 'application/json'],
            ]);
            $data = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (GuzzleException | JsonException) {
            throw new OzonException("Ozon не ответил корректно ($operation).");
        }

        $status = $response->getStatusCode();
        if ($status >= 400) {
            if (($data['error'] ?? null) === 'invalid_refresh_token') {
                throw new OzonException('Доступ к Ozon истёк. Подключите Ozon заново.', 401);
            }
            throw new OzonException("Ozon вернул ошибку HTTP $status ($operation).", $status);
        }
        if ($status !== 200 || !is_array($data)) {
            throw new OzonException("Неожиданный ответ Ozon ($operation).");
        }
        return $data;
    }
}
