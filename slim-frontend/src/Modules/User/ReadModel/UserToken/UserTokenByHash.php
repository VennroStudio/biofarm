<?php

declare(strict_types=1);

namespace App\Modules\User\ReadModel\UserToken;

use App\Modules\User\ReadModel\UserToken\Interface\UserTokenModelInterface;
use Override;

final readonly class UserTokenByHash implements UserTokenModelInterface
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $expiresAt,
        public ?string $revokedAt,
        public ?string $usedAt,
    ) {}

    /**
     * @param array{
     *     id: int,
     *     user_id: int,
     *     expires_at: string,
     *     revoked_at: string|null,
     *     used_at: string|null
     * } $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: $row['id'],
            userId: $row['user_id'],
            expiresAt: $row['expires_at'],
            revokedAt: $row['revoked_at'],
            usedAt: $row['used_at'],
        );
    }

    #[Override]
    public function getId(): int
    {
        return $this->id;
    }

    #[Override]
    public function getUserId(): int
    {
        return $this->userId;
    }

    #[Override]
    public function getExpiresAt(): string
    {
        return $this->expiresAt;
    }

    #[Override]
    public function getRevokedAt(): ?string
    {
        return $this->revokedAt;
    }

    #[Override]
    public function getUsedAt(): ?string
    {
        return $this->usedAt;
    }

    #[Override]
    public function toArray(): array
    {
        return [
            'id'        => $this->id,
            'userId'    => $this->userId,
            'expiresAt' => $this->expiresAt,
            'revokedAt' => $this->revokedAt,
            'usedAt'    => $this->usedAt,
        ];
    }
}
