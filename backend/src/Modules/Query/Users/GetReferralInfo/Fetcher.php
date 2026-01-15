<?php

declare(strict_types=1);

namespace App\Modules\Query\Users\GetReferralInfo;

use App\Modules\Entity\Order\OrderRepository;
use App\Modules\Entity\Settings\SettingsRepository;
use App\Modules\Entity\User\UserRepository;

final readonly class Fetcher
{
    public function __construct(
        private UserRepository $userRepository,
        private OrderRepository $orderRepository,
        private SettingsRepository $settingsRepository,
    ) {}

    public function fetch(Query $query): array
    {
        $referrerIdString = (string)$query->userId;
        
        // Получаем всех зарегистрированных рефералов
        $referrals = $this->userRepository->findByReferredBy($referrerIdString);
        $referredUsers = count($referrals);
        
        // Получаем все заказы рефералов (зарегистрированных и гостевые)
        $referralIds = array_map(fn($user) => $user->getId(), $referrals);
        $allOrders = [];
        
        // Заказы зарегистрированных рефералов
        foreach ($referralIds as $userId) {
            $userOrders = $this->orderRepository->findByUserId($userId);
            $allOrders = array_merge($allOrders, $userOrders);
        }
        
        // Гостевые заказы с referredBy
        $guestOrders = $this->orderRepository->findByReferredBy($referrerIdString);
        $allOrders = array_merge($allOrders, $guestOrders);
        
        // Удаляем дубликаты
        $uniqueOrders = [];
        $seenIds = [];
        foreach ($allOrders as $order) {
            $orderId = $order->getId();
            if (!isset($seenIds[$orderId])) {
                $seenIds[$orderId] = true;
                $uniqueOrders[] = $order;
            }
        }
        
        // Получаем процент рефералов из настроек
        $referralPercent = 5; // По умолчанию
        $settings = $this->settingsRepository->findByKey('referralPercent');
        if ($settings) {
            $referralPercent = (int)$settings->getValue();
        }
        
        // Считаем заработанные бонусы (только оплаченные заказы)
        // Используем bonusEarned из заказа, если он уже начислен, иначе считаем по проценту
        $totalEarnings = 0;
        $pendingEarnings = 0;
        
        foreach ($uniqueOrders as $order) {
            $isPaid = $order->getPaymentStatus() === 'completed' || $order->getPaidAt() !== null;
            
            // Если бонусы уже начислены, используем их, иначе считаем по проценту
            $earnings = $order->getBonusEarned() > 0 
                ? $order->getBonusEarned() 
                : (int)($order->getTotal() * $referralPercent / 100);
            
            if ($isPaid) {
                $totalEarnings += $earnings;
            } else {
                $pendingEarnings += $earnings;
            }
        }
        
        return [
            'referredUsers' => $referredUsers,
            'totalEarnings' => $totalEarnings,
            'pendingEarnings' => $pendingEarnings,
            'referralPercent' => $referralPercent,
        ];
    }
}
