<?php

declare(strict_types=1);

namespace App\Modules\Entity\Admin;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: Admin::DB_NAME)]
#[ORM\UniqueConstraint(name: 'UNIQUE_ADMIN_EMAIL', columns: ['email'])]
#[ORM\Index(fields: ['isActive'], name: 'IDX_ADMIN_ACTIVE')]
#[ORM\Index(fields: ['role'], name: 'IDX_ADMIN_ROLE')]
final class Admin
{
    public const DB_NAME = 'biofarm_admins';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $passwordHash;

    #[ORM\Column(type: 'string', length: 20)]
    private string $role; // 'admin' | 'moderator'

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'integer')]
    private int $createdAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $updatedAt = null;

    private function __construct(
        string $email,
        string $name,
        string $passwordHash,
        string $role = 'admin',
        bool $isActive = true,
    ) {
        $this->email = $email;
        $this->name = $name;
        $this->passwordHash = $passwordHash;
        $this->role = $role;
        $this->isActive = $isActive;
        $this->createdAt = time();
    }

    public static function create(
        string $email,
        string $name,
        string $passwordHash,
        string $role = 'admin',
        bool $isActive = true,
    ): self {
        return new self(
            email: $email,
            name: $name,
            passwordHash: $passwordHash,
            role: $role,
            isActive: $isActive,
        );
    }

    public function updatePassword(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
        $this->updatedAt = time();
    }

    public function edit(
        ?string $name = null,
        ?string $role = null,
        ?bool $isActive = null,
    ): void {
        if ($name !== null) {
            $this->name = $name;
        }
        if ($role !== null) {
            $this->role = $role;
        }
        if ($isActive !== null) {
            $this->isActive = $isActive;
        }
        $this->updatedAt = time();
    }

    public function getId(): int
    {
        if (null === $this->id) {
            throw new \RuntimeException('Admin ID is not set');
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

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function isActive(): bool
    {
        return $this->isActive;
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
