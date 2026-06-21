<?php

declare(strict_types=1);

namespace App\Modules\User\Entity\User;

use App\Components\Clock\UtcClock;
use App\Components\Exception\DomainExceptionModule;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use App\Modules\User\Entity\User\Fields\Enums\UserStatus;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) ?int $id = null;

    #[ORM\Column(type: Types::INTEGER, enumType: UserRole::class)]
    private(set) UserRole $role;

    #[ORM\Column(type: Types::STRING, length: 512, nullable: true)]
    private(set) ?string $avatar = null;

    #[ORM\Column(type: Types::STRING, length: 60)]
    private(set) string $lastName;

    #[ORM\Column(type: Types::STRING, length: 60)]
    private(set) string $firstName;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private(set) string $email;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $password;

    #[ORM\Column(type: Types::INTEGER, enumType: UserStatus::class)]
    private(set) UserStatus $status;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private(set) DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $deletedAt = null;

    /**
     * @throws DateMalformedStringException
     */
    private function __construct(
        string $lastName,
        string $firstName,
        string $email,
        string $password,
    ) {
        $this->role = UserRole::USER;
        $this->lastName = $lastName;
        $this->firstName = $firstName;
        $this->email = $email;
        $this->password = $password;
        $this->status = UserStatus::PENDING_VERIFICATION;

        $this->createdAt = UtcClock::now();
        $this->updatedAt = null;
        $this->deletedAt = null;
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function create(
        string $lastName,
        string $firstName,
        string $email,
        string $password,
    ): self {
        return new self(
            lastName: $lastName,
            firstName: $firstName,
            email: $email,
            password: $password,
        );
    }

    /**
     * @throws DateMalformedStringException
     */
    public function edit(
        string $lastName,
        string $firstName,
    ): void {
        $this->assertNotDeleted();
        $this->lastName = $lastName;
        $this->firstName = $firstName;
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function setAvatar(?string $avatarUrl): void
    {
        $this->assertNotDeleted();
        $this->avatar = $avatarUrl;
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function setPassword(string $password): void
    {
        $this->assertNotDeleted();
        $this->password = $password;
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function changeStatus(UserStatus $status): void
    {
        $this->assertNotDeleted();

        if ($this->status === $status) {
            return;
        }

        $this->status = $status;
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function activate(): void
    {
        $this->changeStatus(UserStatus::ACTIVE);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function ban(): void
    {
        $this->changeStatus(UserStatus::BANNED);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function changeRole(UserRole $role): void
    {
        $this->assertNotDeleted();

        if ($this->role === $role) {
            return;
        }

        $this->role = $role;
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function markDeleted(): void
    {
        $this->assertNotDeleted();

        $this->deletedAt = UtcClock::now();
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    private function touch(): void
    {
        $this->updatedAt = UtcClock::now();
    }

    private function assertNotDeleted(): void
    {
        if ($this->deletedAt !== null) {
            throw new DomainExceptionModule(
                module: 'user',
                message: 'error.user_is_deleted',
                code: 9
            );
        }
    }
}
