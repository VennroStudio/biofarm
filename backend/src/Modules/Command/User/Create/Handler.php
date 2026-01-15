<?php

declare(strict_types=1);

namespace App\Modules\Command\User\Create;

use App\Modules\Entity\User\User;
use App\Modules\Entity\User\UserRepository;
use DomainException;

final readonly class Handler
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function handle(Command $command): User
    {
        $existing = $this->userRepository->findByEmail($command->email);

        if ($existing) {
            throw new DomainException('User with this email already exists');
        }

        $user = User::create(
            email: $command->email,
            name: $command->name,
            passwordHash: $command->passwordHash,
            phone: $command->phone,
            referredBy: $command->referredBy,
            isPartner: $command->isPartner,
            isActive: $command->isActive,
        );

        $this->userRepository->add($user);

        return $user;
    }
}
