<?php

declare(strict_types=1);

namespace App\Modules\Order\Entity\Order\Persistence\Doctrine;

use App\Components\Exception\DomainExceptionModule;
use App\Modules\Order\Entity\Order\Order;
use App\Modules\Order\Entity\Order\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Override;

final readonly class DoctrineOrderRepository implements OrderRepository
{
    /** @var EntityRepository<Order> */
    private EntityRepository $repo;

    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->repo = $this->em->getRepository(Order::class);
    }

    #[Override]
    public function add(Order $order): void
    {
        $this->em->persist($order);
    }

    #[Override]
    public function remove(Order $order): void
    {
        $this->em->remove($order);
    }

    #[Override]
    public function getById(string $id): Order
    {
        if (!$order = $this->findById($id)) {
            throw new DomainExceptionModule(
                module: 'order',
                message: 'error.order_not_found',
                code: 1,
            );
        }

        return $order;
    }

    #[Override]
    public function findById(string $id): ?Order
    {
        return $this->repo->findOneBy(['id' => $id]);
    }
}
