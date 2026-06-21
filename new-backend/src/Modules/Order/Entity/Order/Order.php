<?php

declare(strict_types=1);

namespace App\Modules\Order\Entity\Order;

use App\Components\Clock\UtcClock;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
#[ORM\Index(name: 'idx_orders_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_orders_status', columns: ['status'])]
#[ORM\Index(name: 'idx_orders_payment_status', columns: ['payment_status'])]
#[ORM\Index(name: 'idx_orders_referred_by', columns: ['referred_by'])]
class Order
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 50)]
    private(set) string $id;

    #[ORM\Column(type: Types::INTEGER)]
    private(set) int $userId;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private(set) string $status;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private(set) string $paymentStatus;

    #[ORM\Column(type: Types::INTEGER)]
    private(set) int $total;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private(set) int $bonusUsed;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private(set) int $bonusEarned = 0;

    /**
     * @var array{
     *     name?: string|null,
     *     phone?: string|null,
     *     email?: string|null,
     *     city?: string|null,
     *     address?: string|null,
     *     postal_code?: string|null,
     *     postalCode?: string|null
     * }
     */
    #[ORM\Column(type: Types::JSON)]
    private(set) array $shippingAddress;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private(set) string $paymentMethod;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private(set) ?string $trackingNumber = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private(set) ?string $referredBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private(set) DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $paidAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $updatedAt = null;

    /**
     * @param array{
     *     name?: string|null,
     *     phone?: string|null,
     *     email?: string|null,
     *     city?: string|null,
     *     address?: string|null,
     *     postal_code?: string|null,
     *     postalCode?: string|null
     * } $shippingAddress
     * @throws DateMalformedStringException
     */
    private function __construct(
        string $id,
        int $userId,
        int $total,
        array $shippingAddress,
        string $paymentMethod,
        int $bonusUsed,
        string $status,
        string $paymentStatus,
        ?string $referredBy,
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->total = $total;
        $this->shippingAddress = $shippingAddress;
        $this->paymentMethod = $paymentMethod;
        $this->bonusUsed = $bonusUsed;
        $this->status = $status;
        $this->paymentStatus = $paymentStatus;
        $this->referredBy = $referredBy;
        $this->createdAt = UtcClock::now();
    }

    /**
     * @param array{
     *     name?: string|null,
     *     phone?: string|null,
     *     email?: string|null,
     *     city?: string|null,
     *     address?: string|null,
     *     postal_code?: string|null,
     *     postalCode?: string|null
     * } $shippingAddress
     * @throws DateMalformedStringException
     */
    public static function create(
        string $id,
        int $userId,
        int $total,
        array $shippingAddress,
        string $paymentMethod,
        int $bonusUsed = 0,
        string $status = 'pending',
        string $paymentStatus = 'pending',
        ?string $referredBy = null,
    ): self {
        return new self($id, $userId, $total, $shippingAddress, $paymentMethod, $bonusUsed, $status, $paymentStatus, $referredBy);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function updateStatus(string $status): void
    {
        $this->status = $status;
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function updatePaymentStatus(string $paymentStatus): void
    {
        $this->paymentStatus = $paymentStatus;
        if ($paymentStatus === 'completed' && $this->paidAt === null) {
            $this->paidAt = UtcClock::now();
        }
        $this->touch();
    }

    /**
     * @param array{
     *     name?: string|null,
     *     phone?: string|null,
     *     email?: string|null,
     *     city?: string|null,
     *     address?: string|null,
     *     postal_code?: string|null,
     *     postalCode?: string|null
     * } $shippingAddress
     * @throws DateMalformedStringException
     */
    public function edit(
        int $userId,
        string $status,
        string $paymentStatus,
        int $total,
        int $bonusUsed,
        int $bonusEarned,
        array $shippingAddress,
        string $paymentMethod,
        ?string $trackingNumber,
        ?string $referredBy,
    ): void {
        $this->userId = $userId;
        $this->status = $status;
        $this->paymentStatus = $paymentStatus;
        $this->total = $total;
        $this->bonusUsed = $bonusUsed;
        $this->bonusEarned = $bonusEarned;
        $this->shippingAddress = $shippingAddress;
        $this->paymentMethod = $paymentMethod;
        $this->trackingNumber = $trackingNumber;
        $this->referredBy = $referredBy;

        if ($paymentStatus === 'completed' && $this->paidAt === null) {
            $this->paidAt = UtcClock::now();
        }

        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function setBonusEarned(int $amount): void
    {
        $this->bonusEarned = $amount;
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function setTrackingNumber(?string $trackingNumber): void
    {
        $this->trackingNumber = $trackingNumber;
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
