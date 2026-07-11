<?php

declare(strict_types=1);

namespace App\Modules\Withdrawal\Entity\WithdrawalRequest;

use App\Components\Clock\UtcClock;
use App\Components\Exception\DomainExceptionModule;
use App\Modules\Withdrawal\Entity\WithdrawalRequest\Fields\Enums\WithdrawalRequestStatus;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'withdrawal_requests')]
#[ORM\Index(name: 'idx_withdrawal_requests_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_withdrawal_requests_status', columns: ['status'])]
class WithdrawalRequest
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 50)]
    private(set) string $id;

    #[ORM\Column(name: 'user_id', type: Types::INTEGER)]
    private(set) int $userId;

    #[ORM\Column(type: Types::INTEGER)]
    private(set) int $amount;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: WithdrawalRequestStatus::class)]
    private(set) WithdrawalRequestStatus $status;

    #[ORM\Column(name: 'processed_by', type: Types::STRING, length: 255, nullable: true)]
    private(set) ?string $processedBy = null;

    #[ORM\Column(name: 'processed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $processedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private(set) DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $updatedAt = null;

    /**
     * @throws DateMalformedStringException
     */
    private function __construct(string $id, int $userId, int $amount)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->amount = $amount;
        $this->status = WithdrawalRequestStatus::PENDING;
        $this->createdAt = UtcClock::now();
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function create(string $id, int $userId, int $amount): self
    {
        return new self($id, $userId, $amount);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function approve(string $processedBy): void
    {
        $this->assertPending();
        $this->status = WithdrawalRequestStatus::APPROVED;
        $this->processedBy = $processedBy;
        $this->processedAt = UtcClock::now();
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function reject(string $processedBy): void
    {
        $this->assertPending();
        $this->status = WithdrawalRequestStatus::REJECTED;
        $this->processedBy = $processedBy;
        $this->processedAt = UtcClock::now();
        $this->touch();
    }

    private function assertPending(): void
    {
        if ($this->status !== WithdrawalRequestStatus::PENDING) {
            throw new DomainExceptionModule(
                module: 'withdrawal',
                message: 'error.withdrawal_request_already_processed',
                code: 2,
            );
        }
    }

    /**
     * @throws DateMalformedStringException
     */
    private function touch(): void
    {
        $this->updatedAt = UtcClock::now();
    }
}
