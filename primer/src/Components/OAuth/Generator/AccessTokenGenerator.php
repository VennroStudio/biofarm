<?php

declare(strict_types=1);

namespace App\Components\OAuth\Generator;

use App\Components\OAuth\Entity\AccessToken;
use Exception;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use Ramsey\Uuid\Uuid;

final readonly class AccessTokenGenerator
{
    public function __construct(
        private string $privateKeyPath
    ) {}

    /**
     * @param ScopeEntityInterface[] $scopes
     * @throws Exception
     */
    public function generate(ClientEntityInterface $client, array $scopes, AccessTokenParams $params): AccessToken
    {
        $accessToken = new AccessToken($client, $scopes);

        $accessToken->setIdentifier(Uuid::uuid4()->toString());
        $accessToken->setExpiryDateTime($params->expires);
        $accessToken->setUserIdentifier($params->userId);
        $accessToken->setUserRole($params->role);

        $accessToken->setPrivateKey(new CryptKey($this->privateKeyPath, null, false));

        return $accessToken;
    }
}
