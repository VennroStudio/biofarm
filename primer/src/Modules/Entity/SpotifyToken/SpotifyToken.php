<?php

declare(strict_types=1);

namespace App\Modules\Entity\SpotifyToken;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: SpotifyToken::DB_NAME)]
final class SpotifyToken
{
    public const DB_NAME = 'spotify_token';

    private const STATUS_ON = 1;
    private const STATUS_OFF = 0;

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'string', length: 500)]
    private string $comment;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $cookies;

    #[ORM\Column(type: 'string', length: 500)]
    private string $accessToken;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $refreshToken;

    #[ORM\Column(type: 'integer')]
    private int $status;

    #[ORM\Column(type: 'integer')]
    private int $updatedAt;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage;

    private function __construct(
        string $comment,
        ?string $cookies,
        string $accessToken,
        ?string $refreshToken,
        int $status,
    ) {
        $this->comment = $comment;
        $this->cookies = $cookies;
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->status = $status;
        $this->errorMessage = null;
        $this->updatedAt = time();
    }

    public static function create(
        string $comment,
        ?string $cookies,
        string $accessToken,
        ?string $refreshToken,
        int $status
    ): self {
        return new self(
            comment: $comment,
            cookies: $cookies,
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            status: $status,
        );
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

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): void
    {
        $this->comment = $comment;
    }

    public function getCookies(): ?string
    {
        return $this->cookies;
    }

    public function setCookies(?string $cookies): void
    {
        $this->cookies = $cookies;
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

    public function getStatus(): int
    {
        return $this->status;
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
