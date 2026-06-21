<?php

declare(strict_types=1);

namespace App\Components\Auth;

use App\Components\Clock\UtcClock;
use DateMalformedStringException;
use UnexpectedValueException;

final readonly class RefreshTokenPayload
{
    public function __construct(
        public string $jti,
        public int $userId,
        public int $issuedAt,
        public int $expiresAt,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public static function issue(int $userId, int $ttl): self
    {
        $issuedAt = UtcClock::now()->getTimestamp();

        return new self(
            jti: bin2hex(random_bytes(16)),
            userId: $userId,
            issuedAt: $issuedAt,
            expiresAt: $issuedAt + $ttl,
        );
    }

    public function toClaims(): array
    {
        return [
            'jti' => $this->jti,
            'sub' => (string)$this->userId,
            'iat' => $this->issuedAt,
            'exp' => $this->expiresAt,
        ];
    }

    public static function fromObject(object $payload): self
    {
        return new self(
            jti: self::requireNonEmptyString($payload->jti ?? null),
            userId: self::requirePositiveInt($payload->sub ?? null),
            issuedAt: self::requirePositiveInt($payload->iat ?? null),
            expiresAt: self::requirePositiveInt($payload->exp ?? null),
        );
    }

    private static function requirePositiveInt(mixed $value): int
    {
        $intValue = filter_var($value, FILTER_VALIDATE_INT);

        if (!\is_int($intValue) || $intValue <= 0) {
            throw new UnexpectedValueException('error.invalid_claim');
        }

        return $intValue;
    }

    private static function requireNonEmptyString(mixed $value): string
    {
        if (!\is_string($value) || trim($value) === '') {
            throw new UnexpectedValueException('error.invalid_claim');
        }

        return $value;
    }
}
