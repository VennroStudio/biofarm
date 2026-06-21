<?php

declare(strict_types=1);

namespace App\Components\Auth;

use App\Components\Clock\UtcClock;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use DateMalformedStringException;
use UnexpectedValueException;

final readonly class AccessTokenPayload
{
    public function __construct(
        public string $jti,
        public int $userId,
        public string $firstName,
        public UserRole $role,
        public int $issuedAt,
        public int $expiresAt,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public static function issue(
        int $userId,
        string $firstName,
        UserRole $role,
        int $ttl,
    ): self {
        $issuedAt = UtcClock::now()->getTimestamp();

        return new self(
            jti: bin2hex(random_bytes(16)),
            userId: $userId,
            firstName: $firstName,
            role: $role,
            issuedAt: $issuedAt,
            expiresAt: $issuedAt + $ttl,
        );
    }

    public function toClaims(): array
    {
        return [
            'jti'        => $this->jti,
            'sub'        => (string)$this->userId,
            'first_name' => $this->firstName,
            'role'       => $this->role->value,
            'iat'        => $this->issuedAt,
            'exp'        => $this->expiresAt,
        ];
    }

    public static function fromObject(object $payload): self
    {
        $userId = self::requirePositiveInt($payload->sub ?? null);

        $firstName = self::requireNonEmptyString($payload->first_name ?? null);

        $role = UserRole::from(self::requirePositiveInt($payload->role ?? null));

        return new self(
            jti: self::requireNonEmptyString($payload->jti ?? null),
            userId: $userId,
            firstName: $firstName,
            role: $role,
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
