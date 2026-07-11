<?php

declare(strict_types=1);

namespace App\Modules\Withdrawal\Entity\WithdrawalRequest\Persistence\Doctrine;

use App\Components\Exception\DomainExceptionModule;
use App\Modules\Withdrawal\Entity\WithdrawalRequest\WithdrawalRequest;
use App\Modules\Withdrawal\Entity\WithdrawalRequest\WithdrawalRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Override;

final readonly class DoctrineWithdrawalRequestRepository implements WithdrawalRequestRepository
{
    /** @var EntityRepository<WithdrawalRequest> */
    private EntityRepository $repo;

    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->repo = $em->getRepository(WithdrawalRequest::class);
    }

    #[Override]
    public function add(WithdrawalRequest $request): void
    {
        $this->em->persist($request);
    }

    #[Override]
    public function getById(string $id): WithdrawalRequest
    {
        if (!$request = $this->findById($id)) {
            throw new DomainExceptionModule(
                module: 'withdrawal',
                message: 'error.withdrawal_request_not_found',
                code: 1,
            );
        }

        return $request;
    }

    #[Override]
    public function findById(string $id): ?WithdrawalRequest
    {
        return $this->repo->findOneBy(['id' => $id]);
    }
}
