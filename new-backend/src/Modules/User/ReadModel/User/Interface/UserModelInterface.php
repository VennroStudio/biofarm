<?php

declare(strict_types=1);

namespace App\Modules\User\ReadModel\User\Interface;

interface UserModelInterface
{
    public function getId(): int;

    /**
     * @return array{
     * id: int,
     * email: string,
     * first_name: string,
     * role: array{id: int, label: string},
     * status: array{id: int, label: string},
     * avatar?: string|null,
     * last_name?: string,
     * created_at?: string,
     * updated_at?: string|null,
     * deleted_at?: string|null
     * }
     */
    public function toArray(): array;
}
