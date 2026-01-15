<?php

declare(strict_types=1);

namespace App\Modules\Command\User\Update;

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
        $user = $this->userRepository->getById($command->userId);

        $user->edit(
            name: $command->name ?? $user->getName(),
            phone: $command->phone ?? $user->getPhone(),
            cardNumber: $command->cardNumber ?? $user->getCardNumber(),
            isPartner: $command->isPartner ?? $user->isPartner(),
            isActive: $command->isActive ?? $user->isActive(),
        );

        return $user;
    }
}
