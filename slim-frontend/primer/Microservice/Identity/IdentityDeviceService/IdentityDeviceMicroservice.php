<?php

declare(strict_types=1);

namespace App\Components\Microservice\Identity\IdentityDeviceService;

use App\Components\Microservice\MicroserviceClient;
use DomainException;
use DuckBug\Duck;
use GuzzleHttp\Client;
use Symfony\Component\Translation\Translator;

readonly class IdentityDeviceMicroservice extends MicroserviceClient
{
    private string $token;

    public function __construct(
        Client $client,
        string $host,
        string $token,
        Translator $translator
    ) {
        parent::__construct($client, $host, Duck::get(), $translator);

        $this->token = $token;
    }

    public function save(array $data): ?string
    {
        $result = $this->post(
            uri: '/api/v1/devices',
            data: $data,
            headers: [
                'X-SERVICE-TOKEN' => $this->token,
            ]
        );

        if (isset($result['data']['id']) && \is_string($result['data']['id'])) {
            return $result['data']['id'];
        }

        $this->duck->error(
            message: 'IdentityDeviceMicroservice -> save()',
            context: [
                'data'      => $data,
                'response'  => $result,
            ]
        );

        return null;
    }

    public function deactivate(string $id): void
    {
        $result = $this->post(
            uri: '/api/v1/devices/' . $id . '/deactivate',
            headers: [
                'X-SERVICE-TOKEN' => $this->token,
            ]
        );

        if (isset($result['data']['success']) && $result['data']['success'] === 1) {
            return;
        }

        $this->duck->error(
            message: 'IdentityDeviceMicroservice -> deactivate()',
            context: [
                'id'        => $id,
                'response'  => $result,
            ]
        );
    }

    // Phase 4.1: tokens()/tokensInvalidate() удалены — push-flow перешёл в Go
    // (push-service резолвит push/voip токены через device-service gRPC сам).

    public function biometricVerify(int $userId, string $deviceUuid, string $signature): void
    {
        $result = $this->post(
            uri: '/api/v1/biometric/verify',
            data: [
                'userId'        => $userId,
                'deviceUuid'    => $deviceUuid,
                'signature'     => $signature,
            ],
            headers: [
                'X-SERVICE-TOKEN' => $this->token,
            ]
        );

        if (isset($result['data']['success']) && $result['data']['success'] === 1) {
            return;
        }

        $this->duck->error(
            message: 'IdentityDeviceMicroservice -> biometricVerify()',
            context: [
                'deviceUuid'    => $deviceUuid,
                'signature'     => $signature,
                'response'      => $result,
            ]
        );

        throw new DomainException('Biometric verify failed');
    }
}
