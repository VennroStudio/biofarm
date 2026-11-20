<?php

declare(strict_types=1);

namespace App\Components\Auth;

use App\Components\Exception\AuthenticationException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;
use UnexpectedValueException;

final readonly class JwtTokenService
{
    private const string ALGORITHM = 'HS256';
    private const string TYPE_ACCESS = 'access';
    private const string TYPE_REFRESH = 'refresh';

    public function __construct(
        private string $secret,
        private int $accessTtl = 900,
        private int $refreshTtl = 2592000,
    ) {}

    public function getAccessTtl(): int
    {
        return $this->accessTtl;
    }

    public function getRefreshTtl(): int
    {
        return $this->refreshTtl;
    }

    public function generateAccessToken(AccessTokenPayload $payload): string
    {
        return $this->encode(array_merge($payload->toClaims(), ['type' => self::TYPE_ACCESS]));
    }

    public function generateRefreshToken(RefreshTokenPayload $payload): string
    {
        return $this->encode(array_merge($payload->toClaims(), ['type' => self::TYPE_REFRESH]));
    }

    public function decodeAccessToken(string $token): AccessTokenPayload
    {
        try {
            return AccessTokenPayload::fromObject($this->decodeByType($token, self::TYPE_ACCESS));
        } catch (Throwable $exception) {
            throw new AuthenticationException('error.invalid_token', previous: $exception);
        }
    }

    public function decodeRefreshToken(string $token): RefreshTokenPayload
    {
        try {
            return RefreshTokenPayload::fromObject($this->decodeByType($token, self::TYPE_REFRESH));
        } catch (Throwable $exception) {
            throw new AuthenticationException('error.invalid_token', previous: $exception);
        }
    }

    private function encode(array $payload): string
    {
        return JWT::encode(
            $payload,
            $this->secret,
            self::ALGORITHM,
        );
    }

    private function decodeByType(string $token, string $expectedType): object
    {
        $payload = JWT::decode($token, new Key($this->secret, self::ALGORITHM));

        if (($payload->type ?? '') !== $expectedType) {
            throw new UnexpectedValueException('error.wrong_token_type');
        }

        return $payload;
    }
}
