<?php

declare(strict_types=1);

namespace App\Modules\User\Entity\UserToken\Persistence\Doctrine;

use App\Components\Exception\DomainExceptionModule;
use App\Modules\User\Entity\UserToken\Fields\Enums\UserTokenType;
use App\Modules\User\Entity\UserToken\UserToken;
use App\Modules\User\Entity\UserToken\UserTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Override;

final readonly class DoctrineUserTokenRepository implements UserTokenRepository
{
    /** @var EntityRepository<UserToken> */
    private EntityRepository $repo;

    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->repo = $em->getRepository(UserToken::class);
    }

    #[Override]
    public function add(UserToken $token): void
    {
        $this->em->persist($token);
    }

    #[Override]
    public function getById(int $id): UserToken
    {
        if (!$userToken = $this->findById($id)) {
            throw new DomainExceptionModule(
                module: 'userToken',
                message: 'error.userToken_not_found',
                code: 1
            );
        }

        return $userToken;
    }

    #[Override]
    public function findById(int $id): ?UserToken
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    #[Override]
    public function findByUserIdAndType(int $userId, UserTokenType $type): array
    {
        return $this->repo->findBy([
            'userId' => $userId,
            'type'   => $type,
        ]);
    }
}
