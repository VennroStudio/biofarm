<?php

declare(strict_types=1);

namespace App\Modules\Query\Orders\GetByReferrerId;

use App\Modules\Entity\Order\Order;
use App\Modules\Entity\Order\OrderRepository;
use App\Modules\Entity\User\UserRepository;

final readonly class Fetcher
{
    public function __construct(
        private OrderRepository $orderRepository,
        private UserRepository $userRepository,
    ) {}

    /** @return Order[] */
    public function fetch(Query $query): array
    {
        $referrerIdString = (string)$query->referrerId;
        $allOrders = [];
        
        // 1. Получаем заказы зарегистрированных рефералов
        $referrals = $this->userRepository->findByReferredBy($referrerIdString);
        if (!empty($referrals)) {
            $referralIds = array_map(fn($user) => $user->getId(), $referrals);
            foreach ($referralIds as $userId) {
                $userOrders = $this->orderRepository->findByUserId($userId);
                $allOrders = array_merge($allOrders, $userOrders);
            }
        }
        
        // 2. Получаем гостевые заказы с referredBy (когда пользователь не зарегистрирован, но перешел по ссылке)
        $guestOrders = $this->orderRepository->findByReferredBy($referrerIdString);
        $allOrders = array_merge($allOrders, $guestOrders);
        
        // Удаляем дубликаты по ID и сортируем по дате создания (новые первыми)
        $uniqueOrders = [];
        $seenIds = [];
        foreach ($allOrders as $order) {
            $orderId = $order->getId();
            if (!isset($seenIds[$orderId])) {
                $seenIds[$orderId] = true;
                $uniqueOrders[] = $order;
            }
        }
        
        usort($uniqueOrders, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
        
        return $uniqueOrders;
    }
}
