<?php

declare(strict_types=1);

namespace App\Modules\User\ReadModel\UserToken\Interface;

interface UserTokenModelInterface
{
    public function getId(): int;

    public function getUserId(): int;

    public function getExpiresAt(): string;

    public function getRevokedAt(): ?string;

    public function getUsedAt(): ?string;

    public function toArray(): array;
}
