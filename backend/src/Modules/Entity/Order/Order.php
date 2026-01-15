<?php

declare(strict_types=1);

namespace App\Modules\Entity\Order;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: Order::DB_NAME)]
#[ORM\Index(fields: ['userId'], name: 'IDX_USER')]
#[ORM\Index(fields: ['status'], name: 'IDX_STATUS')]
#[ORM\Index(fields: ['paymentStatus'], name: 'IDX_PAYMENT_STATUS')]
final class Order
{
    public const DB_NAME = 'biofarm_orders';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $id;

    #[ORM\Column(type: 'bigint')]
    private int $userId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status; // pending, paid, shipped, delivered, cancelled

    #[ORM\Column(type: 'string', length: 20)]
    private string $paymentStatus; // pending, completed, failed

    #[ORM\Column(type: 'integer')]
    private int $total;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $bonusUsed = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $bonusEarned = 0;

    #[ORM\Column(type: 'json')]
    private array $shippingAddress; // name, phone, email, city, address, postal_code

    #[ORM\Column(type: 'string', length: 20)]
    private string $paymentMethod; // card, sbp, etc

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $trackingNumber = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $referredBy = null;

    #[ORM\Column(type: 'integer')]
    private int $createdAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $paidAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $updatedAt = null;

    private function __construct(
        string $id,
        int $userId,
        int $total,
        array $shippingAddress,
        string $paymentMethod,
        int $bonusUsed = 0,
        string $status = 'pending',
        string $paymentStatus = 'pending',
        ?string $referredBy = null,
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
        $this->createdAt = time();
    }

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
        return new self(
            id: $id,
            userId: $userId,
            total: $total,
            shippingAddress: $shippingAddress,
            paymentMethod: $paymentMethod,
            bonusUsed: $bonusUsed,
            status: $status,
            paymentStatus: $paymentStatus,
            referredBy: $referredBy,
        );
    }

    public function updateStatus(string $status): void
    {
        $this->status = $status;
        $this->updatedAt = time();
    }

    public function updatePaymentStatus(string $paymentStatus): void
    {
        $this->paymentStatus = $paymentStatus;
        if ($paymentStatus === 'completed' && $this->paidAt === null) {
            $this->paidAt = time();
        }
        $this->updatedAt = time();
    }

    public function setBonusEarned(int $amount): void
    {
        $this->bonusEarned = $amount;
        $this->updatedAt = time();
    }

    public function setTrackingNumber(string $trackingNumber): void
    {
        $this->trackingNumber = $trackingNumber;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getBonusUsed(): int
    {
        return $this->bonusUsed;
    }

    public function getBonusEarned(): int
    {
        return $this->bonusEarned;
    }

    public function getShippingAddress(): array
    {
        return $this->shippingAddress;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getPaidAt(): ?int
    {
        return $this->paidAt;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }

    public function getReferredBy(): ?string
    {
        return $this->referredBy;
    }
}
