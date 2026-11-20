<?php

declare(strict_types=1);

namespace App\Modules\User\Entity\UserProfile;

interface UserProfileRepository
{
    public function add(UserProfile $profile): void;

    public function getByUserId(int $userId): UserProfile;

    public function findByUserId(int $userId): ?UserProfile;

    public function findByReferralCode(string $referralCode): ?UserProfile;
}
