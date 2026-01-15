<?php

declare(strict_types=1);

namespace App\Modules\Command\Order\UpdatePaymentStatus;

use App\Modules\Entity\Order\Order;
use App\Modules\Entity\Order\OrderRepository;
use App\Modules\Entity\User\UserRepository;
use App\Modules\Entity\Settings\SettingsRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class Handler
{
    public function __construct(
        private OrderRepository $orderRepository,
        private UserRepository $userRepository,
        private SettingsRepository $settingsRepository,
        private EntityManagerInterface $em,
    ) {}

    public function handle(Command $command): Order
    {
        $order = $this->orderRepository->getById($command->orderId);
        $oldPaymentStatus = $order->getPaymentStatus();

        $order->updatePaymentStatus($command->paymentStatus);

        // Если заказ оплачен и есть реферер, начисляем бонусы партнеру
        if ($command->paymentStatus === 'completed' && $oldPaymentStatus !== 'completed' && $order->getReferredBy()) {
            $this->awardReferralBonus($order);
        }

        return $order;
    }

    private function awardReferralBonus(Order $order): void
    {
        $referredBy = $order->getReferredBy();
        if (!$referredBy) {
            return;
        }

        // Получаем партнера
        $referrer = $this->userRepository->findById((int)$referredBy);
        if (!$referrer || !$referrer->isPartner()) {
            return;
        }

        // Получаем процент рефералов из настроек
        $referralPercent = 5; // По умолчанию
        $settings = $this->settingsRepository->findByKey('referralPercent');
        if ($settings) {
            $referralPercent = (int)$settings->getValue();
        }

        // Рассчитываем бонусы (процент от суммы заказа)
        $bonusAmount = (int)($order->getTotal() * $referralPercent / 100);

        // Начисляем бонусы партнеру
        $referrer->addBonus($bonusAmount);

        // Сохраняем сумму бонусов в заказе
        $order->setBonusEarned($bonusAmount);
        
        // Сохраняем изменения пользователя в EntityManager
        $this->em->persist($referrer);
    }
}
