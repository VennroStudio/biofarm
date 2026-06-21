<?php

declare(strict_types=1);

namespace App\Modules\User\ReadModel\User;

use App\Components\ReadModel\FromRowsTrait;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use App\Modules\User\Entity\User\Fields\Enums\UserStatus;
use App\Modules\User\ReadModel\User\Interface\UserModelInterface;
use Override;

final readonly class UserById implements UserModelInterface
{
    use FromRowsTrait;

    public function __construct(
        public int $id,
        public UserRole $role,
        public UserStatus $status,
        public string $lastName,
        public string $firstName,
        public string $email,
        public ?string $avatar,
        public string $createdAt,
        public ?string $updatedAt,
        public ?string $deletedAt,
    ) {}

    /**
     * @param array{
     *     id: int,
     *     role: int,
     *     status: int,
     *     last_name: string,
     *     first_name: string,
     *     email: string,
     *     avatar: string|null,
     *     created_at: string,
     *     updated_at: string|null,
     *     deleted_at: string|null
     * } $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: $row['id'],
            role: UserRole::from($row['role']),
            status: UserStatus::from($row['status']),
            lastName: $row['last_name'],
            firstName: $row['first_name'],
            email: $row['email'],
            avatar: $row['avatar'],
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'],
            deletedAt: $row['deleted_at'],
        );
    }

    #[Override]
    public function getId(): int
    {
        return $this->id;
    }

    #[Override]
    public function toArray(): array
    {
        return [
            'id'   => $this->id,
            'role' => [
                'id'    => $this->role->value,
                'label' => $this->role->getLabel(),
            ],
            'status' => [
                'id'    => $this->status->value,
                'label' => $this->status->getLabel(),
            ],
            'avatar'     => $this->avatar,
            'first_name' => $this->firstName,
            'last_name'  => $this->lastName,
            'email'      => $this->email,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];
    }
}
