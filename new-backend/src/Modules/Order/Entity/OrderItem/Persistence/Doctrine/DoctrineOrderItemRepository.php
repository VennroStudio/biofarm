<?php

declare(strict_types=1);

namespace App\Modules\Order\Entity\OrderItem\Persistence\Doctrine;

use App\Modules\Order\Entity\OrderItem\OrderItem;
use App\Modules\Order\Entity\OrderItem\OrderItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Override;

final readonly class DoctrineOrderItemRepository implements OrderItemRepository
{
    /** @var EntityRepository<OrderItem> */
    private EntityRepository $repo;

    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->repo = $this->em->getRepository(OrderItem::class);
    }

    #[Override]
    public function add(OrderItem $item): void
    {
        $this->em->persist($item);
    }

    #[Override]
    public function remove(OrderItem $item): void
    {
        $this->em->remove($item);
    }

    /**
     * @return list<OrderItem>
     */
    #[Override]
    public function findByOrderId(string $orderId): array
    {
        return $this->repo->findBy(['orderId' => $orderId]);
    }
}
