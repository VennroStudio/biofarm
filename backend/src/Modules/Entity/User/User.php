<?php

declare(strict_types=1);

namespace App\Modules\Entity\User;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: User::DB_NAME)]
#[ORM\UniqueConstraint(name: 'UNIQUE_EMAIL', columns: ['email'])]
#[ORM\Index(fields: ['isActive'], name: 'IDX_ACTIVE')]
#[ORM\Index(fields: ['isPartner'], name: 'IDX_PARTNER')]
final class User
{
    public const DB_NAME = 'biofarm_users';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $passwordHash;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $referredBy = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $bonusBalance = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isPartner = false;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $cardNumber = null;

    #[ORM\Column(type: 'integer')]
    private int $createdAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $updatedAt = null;

    private function __construct(
        string $email,
        string $name,
        string $passwordHash,
        ?string $phone = null,
        ?string $referredBy = null,
        bool $isPartner = false,
        bool $isActive = true,
    ) {
        $this->email = $email;
        $this->name = $name;
        $this->phone = $phone;
        $this->passwordHash = $passwordHash;
        $this->referredBy = $referredBy;
        $this->isPartner = $isPartner;
        $this->isActive = $isActive;
        $this->createdAt = time();
    }

    public static function create(
        string $email,
        string $name,
        string $passwordHash,
        ?string $phone = null,
        ?string $referredBy = null,
        bool $isPartner = false,
        bool $isActive = true,
    ): self {
        return new self(
            email: $email,
            name: $name,
            passwordHash: $passwordHash,
            phone: $phone,
            referredBy: $referredBy,
            isPartner: $isPartner,
            isActive: $isActive,
        );
    }

    public function edit(
        string $name,
        ?string $phone = null,
        ?string $cardNumber = null,
        ?bool $isPartner = null,
        ?bool $isActive = null,
    ): void {
        $this->name = $name;
        $this->phone = $phone;
        $this->cardNumber = $cardNumber;
        if ($isPartner !== null) {
            $this->isPartner = $isPartner;
        }
        if ($isActive !== null) {
            $this->isActive = $isActive;
        }
        $this->updatedAt = time();
    }

    public function addBonus(int $amount): void
    {
        $this->bonusBalance += $amount;
        $this->updatedAt = time();
    }

    public function useBonus(int $amount): void
    {
        if ($this->bonusBalance < $amount) {
            throw new DomainException('Insufficient bonus balance');
        }
        $this->bonusBalance -= $amount;
        $this->updatedAt = time();
    }

    public function setPartner(bool $isPartner): void
    {
        $this->isPartner = $isPartner;
        $this->updatedAt = time();
    }

    public function getId(): int
    {
        if (null === $this->id) {
            throw new DomainException('Id not set');
        }
        return (int)$this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getReferredBy(): ?string
    {
        return $this->referredBy;
    }

    public function getBonusBalance(): int
    {
        return $this->bonusBalance;
    }

    public function isPartner(): bool
    {
        return $this->isPartner;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCardNumber(): ?string
    {
        return $this->cardNumber;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }
}
