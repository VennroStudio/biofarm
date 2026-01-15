<?php

declare(strict_types=1);

namespace App\Modules\Entity\TidalToken;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class TidalTokenRepository
{
    /** @var EntityRepository<TidalToken> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(TidalToken::class);
        $this->em = $em;
    }

    public function getCount(): int
    {
        return $this->repo->count([]);
    }

    /** @throws Exception */
    public function getById(int $id): TidalToken
    {
        if (!$tidalToken = $this->findById($id)) {
            throw new Exception('TidalToken Not Found');
        }

        return $tidalToken;
    }

    public function findById(int $id): ?TidalToken
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    public function findFirstActive(int $type): ?TidalToken
    {
        return $this->repo->findOneBy(['type' => $type, 'status' => TidalToken::statusOn()]);
    }

    public function findLastActive(int $type): ?TidalToken
    {
        return $this->repo->findOneBy(['type' => $type, 'status' => TidalToken::statusOn()], ['id' => 'DESC']);
    }

    public function add(TidalToken $tidalToken): void
    {
        $this->em->persist($tidalToken);
    }

    public function remove(TidalToken $tidalToken): void
    {
        $this->em->remove($tidalToken);
    }
}
