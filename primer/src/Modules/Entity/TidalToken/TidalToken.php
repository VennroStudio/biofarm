<?php

declare(strict_types=1);

namespace App\Modules\Entity\TidalToken;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: TidalToken::DB_NAME)]
final class TidalToken
{
    public const DB_NAME = 'tidal_token';

    public const TYPE_API = 0;
    public const TYPE_DL = 1;
    public const TYPE_WEB = 2;

    private const STATUS_ON = 1;
    private const STATUS_OFF = 0;

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $comment;

    #[ORM\Column(type: 'text')]
    private string $accessToken;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $refreshToken;

    #[ORM\Column(type: 'integer')]
    private int $type;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $clientId = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $clientSecret = null;

    #[ORM\Column(type: 'integer')]
    private int $status;

    #[ORM\Column(type: 'integer')]
    private int $updatedAt;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage;

    private function __construct(
        ?string $comment,
        string $accessToken,
        string $refreshToken,
        int $type,
        int $status,
    ) {
        $this->comment = $comment;
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->type = $type;
        $this->status = $status;
        $this->errorMessage = null;
        $this->updatedAt = time();
    }

    public static function create(
        ?string $comment,
        string $accessToken,
        string $refreshToken,
        int $type,
        int $status
    ): self {
        return new self(
            comment: $comment,
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            type: $type,
            status: $status,
        );
    }

    public function isExpired(): bool
    {
        return $this->updatedAt < time() - 12 * 3600;
    }

    public function refresh(string $accessToken): void
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = null;
        $this->status = self::STATUS_ON;
        $this->errorMessage = null;
        $this->updatedAt = time();
    }

    public static function statusOn(): int
    {
        return self::STATUS_ON;
    }

    public static function statusOff(): int
    {
        return self::STATUS_OFF;
    }

    public function getId(): int
    {
        if (null === $this->id) {
            throw new DomainException('Id not set');
        }
        return (int)$this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): void
    {
        $this->type = $type;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function setClientId(?string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(?string $clientSecret): void
    {
        $this->clientSecret = $clientSecret;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ON;
    }

    public function setStatusOn(): void
    {
        $this->status = self::STATUS_ON;
    }

    public function setStatusOff(): void
    {
        $this->status = self::STATUS_OFF;
    }

    public function getUpdatedAt(): int
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(int $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }

    public function resetErrorMessage(): void
    {
        $this->errorMessage = null;
    }
}
