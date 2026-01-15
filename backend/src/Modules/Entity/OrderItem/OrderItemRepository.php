<?php

declare(strict_types=1);

namespace App\Modules\Entity\OrderItem;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class OrderItemRepository
{
    /** @var EntityRepository<OrderItem> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(OrderItem::class);
        $this->em = $em;
    }

    /** @return OrderItem[] */
    public function findByOrderId(string $orderId): array
    {
        return $this->repo->findBy(['orderId' => $orderId]);
    }

    public function add(OrderItem $item): void
    {
        $this->em->persist($item);
    }

    public function remove(OrderItem $item): void
    {
        $this->em->remove($item);
    }
}
