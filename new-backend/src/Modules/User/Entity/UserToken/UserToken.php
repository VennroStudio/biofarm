<?php

declare(strict_types=1);

namespace App\Modules\User\Entity\UserToken;

use App\Components\Clock\UtcClock;
use App\Modules\User\Entity\UserToken\Fields\Enums\UserTokenState;
use App\Modules\User\Entity\UserToken\Fields\Enums\UserTokenType;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_tokens')]
#[ORM\Index(name: 'idx_user_tokens_user_type', columns: ['user_id', 'type'])]
#[ORM\Index(name: 'idx_user_tokens_expires_at', columns: ['expires_at'])]
#[ORM\UniqueConstraint(name: 'uniq_user_tokens_token_hash', columns: ['token_hash'])]
class UserToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private(set) ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private(set) int $userId;

    #[ORM\Column(type: Types::INTEGER, enumType: UserTokenType::class)]
    private(set) UserTokenType $type;

    #[ORM\Column(type: Types::STRING, length: 64)]
    private(set) string $tokenHash;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private(set) DateTimeImmutable $expiresAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $usedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $revokedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private(set) DateTimeImmutable $createdAt;

    /**
     * @throws DateMalformedStringException
     */
    private function __construct(
        int $userId,
        UserTokenType $type,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
    ) {
        $this->userId = $userId;
        $this->type = $type;
        $this->tokenHash = $tokenHash;
        $this->expiresAt = $expiresAt;

        $this->createdAt = UtcClock::now();
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function create(
        int $userId,
        UserTokenType $type,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
    ): self {
        return new self(
            userId: $userId,
            type: $type,
            tokenHash: $tokenHash,
            expiresAt: $expiresAt,
        );
    }

    /**
     * @throws DateMalformedStringException
     */
    public function markUsed(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $this->usedAt = UtcClock::now();

        return true;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function revoke(): bool
    {
        if ($this->revokedAt !== null) {
            return false;
        }
        $this->revokedAt = UtcClock::now();

        return true;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function isExpired(): bool
    {
        return $this->expiresAt <= UtcClock::now();
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function isActive(): bool
    {
        return $this->getState() === UserTokenState::ACTIVE;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function getState(): UserTokenState
    {
        return match (true) {
            $this->isRevoked() => UserTokenState::REVOKED,
            $this->isUsed()    => UserTokenState::USED,
            $this->isExpired() => UserTokenState::EXPIRED,
            default            => UserTokenState::ACTIVE,
        };
    }
}
