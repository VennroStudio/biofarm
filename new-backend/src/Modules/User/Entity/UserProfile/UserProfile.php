<?php

declare(strict_types=1);

namespace App\Modules\User\Entity\UserProfile;

use App\Components\Clock\UtcClock;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_profiles')]
#[ORM\UniqueConstraint(name: 'uniq_user_profiles_referral_code', columns: ['referral_code'])]
#[ORM\Index(name: 'idx_user_profiles_referred_by_user_id', columns: ['referred_by_user_id'])]
class UserProfile
{
    #[ORM\Id]
    #[ORM\Column(name: 'user_id', type: Types::INTEGER)]
    private(set) int $userId;

    #[ORM\Column(type: Types::STRING, length: 30, nullable: true)]
    private(set) ?string $phone;

    #[ORM\Column(name: 'card_number', type: Types::STRING, length: 32, nullable: true)]
    private(set) ?string $cardNumber;

    #[ORM\Column(name: 'bonus_balance', type: Types::INTEGER, options: ['default' => 0])]
    private(set) int $bonusBalance;

    #[ORM\Column(name: 'is_partner', type: Types::BOOLEAN, options: ['default' => false])]
    private(set) bool $isPartner;

    #[ORM\Column(name: 'referral_code', type: Types::STRING, length: 50, nullable: true)]
    private(set) ?string $referralCode;

    #[ORM\Column(name: 'referred_by_user_id', type: Types::INTEGER, nullable: true)]
    private(set) ?int $referredByUserId;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private(set) DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $updatedAt = null;

    /**
     * @throws DateMalformedStringException
     */
    private function __construct(
        int $userId,
        ?string $phone,
        ?string $cardNumber,
        int $bonusBalance,
        bool $isPartner,
        ?string $referralCode,
        ?int $referredByUserId,
    ) {
        $this->userId = $userId;
        $this->phone = $phone;
        $this->cardNumber = $cardNumber;
        $this->bonusBalance = $bonusBalance;
        $this->isPartner = $isPartner;
        $this->referralCode = $referralCode;
        $this->referredByUserId = $referredByUserId;
        $this->createdAt = UtcClock::now();
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function create(
        int $userId,
        ?string $phone = null,
        ?string $cardNumber = null,
        int $bonusBalance = 0,
        bool $isPartner = false,
        ?string $referralCode = null,
        ?int $referredByUserId = null,
    ): self {
        return new self(
            userId: $userId,
            phone: $phone,
            cardNumber: $cardNumber,
            bonusBalance: $bonusBalance,
            isPartner: $isPartner,
            referralCode: $referralCode,
            referredByUserId: $referredByUserId,
        );
    }

    /**
     * @throws DateMalformedStringException
     */
    public function edit(
        ?string $phone,
        ?string $cardNumber,
        bool $isPartner,
        ?string $referralCode,
        ?int $referredByUserId,
    ): void {
        $this->phone = $phone;
        $this->cardNumber = $cardNumber;
        $this->isPartner = $isPartner;
        $this->referralCode = $referralCode;
        $this->referredByUserId = $referredByUserId;
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function changePartnerStatus(bool $isPartner): void
    {
        if ($this->isPartner === $isPartner) {
            return;
        }

        $this->isPartner = $isPartner;
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function changeBonusBalance(int $bonusBalance): void
    {
        if ($this->bonusBalance === $bonusBalance) {
            return;
        }

        $this->bonusBalance = $bonusBalance;
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function addBonus(int $amount): void
    {
        if ($amount === 0) {
            return;
        }

        $this->bonusBalance += $amount;
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    private function touch(): void
    {
        $this->updatedAt = UtcClock::now();
    }
}
