<?php

declare(strict_types=1);

namespace App\Modules\User\ReadModel\User;

use App\Components\ReadModel\FromRowsTrait;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use App\Modules\User\Entity\User\Fields\Enums\UserStatus;
use App\Modules\User\ReadModel\User\Interface\UserModelInterface;
use Override;

final readonly class UserByEmail implements UserModelInterface
{
    use FromRowsTrait;

    public function __construct(
        public int $id,
        public string $email,
        public string $password,
        public string $firstName,
        public UserRole $role,
        public UserStatus $status,
        public ?string $deletedAt,
    ) {}

    /**
     * @param array{
     *     id: int,
     *     email: string,
     *     password: string,
     *     first_name: string,
     *     role: int,
     *     status: int,
     *     deleted_at: string|null
     * } $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: $row['id'],
            email: $row['email'],
            password: $row['password'],
            firstName: $row['first_name'],
            role: UserRole::from($row['role']),
            status: UserStatus::from($row['status']),
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
            'id'         => $this->id,
            'email'      => $this->email,
            'first_name' => $this->firstName,
            'role'       => [
                'id'    => $this->role->value,
                'label' => $this->role->getLabel(),
            ],
            'status' => [
                'id'    => $this->status->value,
                'label' => $this->status->getLabel(),
            ],
        ];
    }
}
