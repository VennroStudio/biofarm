<?php

declare(strict_types=1);

namespace App\Modules\Bonus\Entity\BonusTransaction;

use App\Components\Clock\UtcClock;
use App\Modules\Bonus\Entity\BonusTransaction\Fields\Enums\BonusTransactionType;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'bonus_transactions')]
#[ORM\Index(name: 'idx_bonus_transactions_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_bonus_transactions_type', columns: ['type'])]
#[ORM\Index(name: 'idx_bonus_transactions_source_order_id', columns: ['source_order_id'])]
class BonusTransaction
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) ?int $id = null;

    #[ORM\Column(name: 'user_id', type: Types::INTEGER)]
    private(set) int $userId;

    #[ORM\Column(type: Types::INTEGER)]
    private(set) int $amount;

    #[ORM\Column(type: Types::STRING, length: 30, enumType: BonusTransactionType::class)]
    private(set) BonusTransactionType $type;

    #[ORM\Column(name: 'source_order_id', type: Types::STRING, length: 50, nullable: true)]
    private(set) ?string $sourceOrderId;

    #[ORM\Column(name: 'source_withdrawal_id', type: Types::STRING, length: 50, nullable: true)]
    private(set) ?string $sourceWithdrawalId;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private(set) ?string $comment;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private(set) DateTimeImmutable $createdAt;

    /**
     * @throws DateMalformedStringException
     */
    private function __construct(
        int $userId,
        int $amount,
        BonusTransactionType $type,
        ?string $sourceOrderId,
        ?string $sourceWithdrawalId,
        ?string $comment,
    ) {
        $this->userId = $userId;
        $this->amount = $amount;
        $this->type = $type;
        $this->sourceOrderId = $sourceOrderId;
        $this->sourceWithdrawalId = $sourceWithdrawalId;
        $this->comment = $comment;
        $this->createdAt = UtcClock::now();
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function create(
        int $userId,
        int $amount,
        BonusTransactionType $type,
        ?string $sourceOrderId = null,
        ?string $sourceWithdrawalId = null,
        ?string $comment = null,
    ): self {
        return new self($userId, $amount, $type, $sourceOrderId, $sourceWithdrawalId, $comment);
    }
}
