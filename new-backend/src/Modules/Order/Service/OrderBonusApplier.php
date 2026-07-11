<?php

declare(strict_types=1);

namespace App\Modules\Order\Service;

use App\Components\Setting\SiteSettings;
use App\Modules\Bonus\Entity\BonusTransaction\BonusTransaction;
use App\Modules\Bonus\Entity\BonusTransaction\BonusTransactionRepository;
use App\Modules\Bonus\Entity\BonusTransaction\Fields\Enums\BonusTransactionType;
use App\Modules\Order\Entity\Order\Order;
use App\Modules\User\Entity\UserProfile\UserProfile;
use App\Modules\User\Entity\UserProfile\UserProfileRepository;
use DateMalformedStringException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class OrderBonusApplier
{
    public function __construct(
        private SiteSettings $settings,
        private UserProfileRepository $profileRepository,
        private BonusTransactionRepository $bonusRepository,
        private Connection $connection,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function apply(Order $order): void
    {
        if ($order->paymentStatus !== 'completed') {
            return;
        }

        $buyerProfile = $this->profileRepository->findByUserId($order->userId);

        if ($buyerProfile !== null && $this->settings->bool('order_bonus_enabled', true)) {
            $this->applyBuyerBonus($order, $buyerProfile);
        }

        $referrerProfile = $this->resolveReferrerProfile($order, $buyerProfile);
        if ($referrerProfile === null || !$referrerProfile->isPartner || $referrerProfile->userId === $order->userId) {
            return;
        }

        $referralBonus = $this->amountByPercent($order->total, $this->settings->int('referral_percent', 5));
        if ($referralBonus <= 0 || $this->hasTransaction($referrerProfile->userId, BonusTransactionType::REFERRAL_BONUS, $order->id)) {
            return;
        }

        $referrerProfile->addBonus($referralBonus);
        $order->setBonusEarned($referralBonus);
        $this->bonusRepository->add(BonusTransaction::create(
            userId: $referrerProfile->userId,
            amount: $referralBonus,
            type: BonusTransactionType::REFERRAL_BONUS,
            sourceOrderId: $order->id,
            comment: 'Реферальный бонус за заказ',
        ));
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    private function applyBuyerBonus(Order $order, UserProfile $buyerProfile): void
    {
        $bonus = $this->amountByPercent($order->total, $this->settings->int('order_bonus_percent', 5));
        if ($bonus <= 0 || $this->hasTransaction($buyerProfile->userId, BonusTransactionType::ORDER_BONUS, $order->id)) {
            return;
        }

        $buyerProfile->addBonus($bonus);
        $this->bonusRepository->add(BonusTransaction::create(
            userId: $buyerProfile->userId,
            amount: $bonus,
            type: BonusTransactionType::ORDER_BONUS,
            sourceOrderId: $order->id,
            comment: 'Бонусы за заказ',
        ));
    }

    private function amountByPercent(int $total, int $percent): int
    {
        if ($total <= 0 || $percent <= 0) {
            return 0;
        }

        return (int)floor($total * ($percent / 100));
    }

    private function resolveReferrerProfile(Order $order, ?UserProfile $buyerProfile): ?UserProfile
    {
        $referredBy = trim((string)$order->referredBy);

        if ($referredBy !== '') {
            if (ctype_digit($referredBy)) {
                return $this->profileRepository->findByUserId((int)$referredBy);
            }

            return $this->profileRepository->findByReferralCode($referredBy);
        }

        if ($buyerProfile?->referredByUserId === null) {
            return null;
        }

        return $this->profileRepository->findByUserId($buyerProfile->referredByUserId);
    }

    /**
     * @throws Exception
     */
    private function hasTransaction(int $userId, BonusTransactionType $type, string $orderId): bool
    {
        return (int)$this->connection->fetchOne(
            'SELECT COUNT(id) FROM bonus_transactions WHERE user_id = :userId AND type = :type AND source_order_id = :orderId',
            [
                'userId'  => $userId,
                'type'    => $type->value,
                'orderId' => $orderId,
            ],
        ) > 0;
    }
}
