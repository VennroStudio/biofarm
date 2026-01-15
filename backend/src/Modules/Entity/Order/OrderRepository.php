<?php

declare(strict_types=1);

namespace App\Modules\Entity\Order;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class OrderRepository
{
    /** @var EntityRepository<Order> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(Order::class);
        $this->em = $em;
    }

    public function getCount(): int
    {
        return $this->repo->count([]);
    }

    /** @throws Exception */
    public function getById(string $id): Order
    {
        if (!$order = $this->findById($id)) {
            throw new Exception('Order Not Found');
        }

        return $order;
    }

    public function findById(string $id): ?Order
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    /** @return Order[] */
    public function findByUserId(int $userId): array
    {
        return $this->repo->findBy(['userId' => $userId], ['createdAt' => 'DESC']);
    }

    /** @return Order[] */
    public function findAll(): array
    {
        return $this->repo->findBy([], ['createdAt' => 'DESC']);
    }

    /** @return Order[] */
    public function findByReferredBy(string $referredBy): array
    {
        return $this->repo->findBy(['referredBy' => $referredBy], ['createdAt' => 'DESC']);
    }

    public function add(Order $order): void
    {
        $this->em->persist($order);
    }

    public function remove(Order $order): void
    {
        $this->em->remove($order);
    }
}
