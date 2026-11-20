<?php

declare(strict_types=1);

namespace App\Modules\User\Entity\UserProfile\Persistence\Doctrine;

use App\Components\Exception\DomainExceptionModule;
use App\Modules\User\Entity\UserProfile\UserProfile;
use App\Modules\User\Entity\UserProfile\UserProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Override;

final readonly class DoctrineUserProfileRepository implements UserProfileRepository
{
    /** @var EntityRepository<UserProfile> */
    private EntityRepository $repo;

    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->repo = $em->getRepository(UserProfile::class);
    }

    #[Override]
    public function add(UserProfile $profile): void
    {
        $this->em->persist($profile);
    }

    #[Override]
    public function getByUserId(int $userId): UserProfile
    {
        if (!$profile = $this->findByUserId($userId)) {
            throw new DomainExceptionModule(
                module: 'user',
                message: 'error.user_profile_not_found',
                code: 30,
            );
        }

        return $profile;
    }

    #[Override]
    public function findByUserId(int $userId): ?UserProfile
    {
        return $this->repo->findOneBy(['userId' => $userId]);
    }

    #[Override]
    public function findByReferralCode(string $referralCode): ?UserProfile
    {
        return $this->repo->findOneBy(['referralCode' => $referralCode]);
    }
}
