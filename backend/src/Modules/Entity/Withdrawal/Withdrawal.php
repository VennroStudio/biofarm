<?php

declare(strict_types=1);

namespace App\Modules\Entity\Withdrawal;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: Withdrawal::DB_NAME)]
#[ORM\Index(fields: ['userId'], name: 'IDX_USER')]
#[ORM\Index(fields: ['status'], name: 'IDX_STATUS')]
final class Withdrawal
{
    public const DB_NAME = 'biofarm_withdrawals';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $id;

    #[ORM\Column(type: 'bigint')]
    private int $userId;

    #[ORM\Column(type: 'integer')]
    private int $amount;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status; // pending, approved, rejected

    #[ORM\Column(type: 'integer')]
    private int $createdAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $processedAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $processedBy = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $updatedAt = null;

    private function __construct(
        string $id,
        int $userId,
        int $amount,
        string $status = 'pending',
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->amount = $amount;
        $this->status = $status;
        $this->createdAt = time();
    }

    public static function create(
        string $id,
        int $userId,
        int $amount,
        string $status = 'pending',
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            amount: $amount,
            status: $status,
        );
    }

    public function updateStatus(string $status, ?string $processedBy = null): void
    {
        $this->status = $status;
        if ($status !== 'pending' && $this->processedAt === null) {
            $this->processedAt = time();
            $this->processedBy = $processedBy;
        }
        $this->updatedAt = time();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getProcessedAt(): ?int
    {
        return $this->processedAt;
    }

    public function getProcessedBy(): ?string
    {
        return $this->processedBy;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }
}
