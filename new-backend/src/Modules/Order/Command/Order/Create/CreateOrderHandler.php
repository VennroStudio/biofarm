<?php

declare(strict_types=1);

namespace App\Modules\Order\Command\Order\Create;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Components\Id\ReadableIdGenerator;
use App\Components\Setting\SiteSettings;
use App\Modules\Bonus\Entity\BonusTransaction\BonusTransaction;
use App\Modules\Bonus\Entity\BonusTransaction\BonusTransactionRepository;
use App\Modules\Bonus\Entity\BonusTransaction\Fields\Enums\BonusTransactionType;
use App\Modules\Order\Entity\Order\Order;
use App\Modules\Order\Entity\Order\OrderRepository;
use App\Modules\Order\Entity\OrderItem\OrderItem;
use App\Modules\Order\Entity\OrderItem\OrderItemRepository;
use App\Modules\Order\Permission\OrderPermission;
use App\Modules\Order\Service\OrderEmailNotifier;
use App\Modules\Order\Service\OrderPermissionService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use App\Modules\User\Entity\UserProfile\UserProfile;
use App\Modules\User\Entity\UserProfile\UserProfileRepository;
use DateMalformedStringException;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Random\RandomException;
use Throwable;

final readonly class CreateOrderHandler
{
    public function __construct(
        private OrderRepository $orderRepository,
        private OrderItemRepository $orderItemRepository,
        private ReadableIdGenerator $idGenerator,
        private OrderPermissionService $permissionService,
        private UserProfileRepository $profileRepository,
        private BonusTransactionRepository $bonusRepository,
        private SiteSettings $settings,
        private OrderEmailNotifier $emailNotifier,
        private Connection $connection,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @return array{id: string, user_id: int, subtotal: int, delivery_method: string, delivery_cost: int, discount_amount: int, bonus_used: int, total: int}
     * @throws DateMalformedStringException
     * @throws Exception
     * @throws RandomException
     */
    public function handle(CreateOrderCommand $command): array
    {
        $this->permissionService->checkRole(
            currentUserRole: UserRole::from($command->currentUserRole),
            action: OrderPermission::CREATE,
        );

        if (!$this->settings->bool('cart_enabled')) {
            throw new DomainExceptionModule(
                module: 'order',
                message: 'error.cart_disabled',
                code: 20,
            );
        }

        $orderId = $command->orderId !== null && trim($command->orderId) !== ''
            ? trim($command->orderId)
            : $this->idGenerator->generate('ORD');
        $calculation = $this->calculate($command);

        $order = Order::create(
            id: $orderId,
            userId: $command->userId,
            total: $calculation['total'],
            subtotal: $calculation['subtotal'],
            deliveryMethod: $calculation['delivery_method'],
            deliveryCost: $calculation['delivery_cost'],
            discountAmount: $calculation['discount_amount'],
            promoCode: $calculation['promo_code'],
            shippingAddress: $command->shippingAddress,
            paymentMethod: $this->normalizePaymentMethod($command->paymentMethod),
            bonusUsed: $calculation['bonus_used'],
            status: $command->status,
            paymentStatus: $command->paymentStatus,
            referredBy: $this->resolveReferredBy($command),
        );

        $this->connection->beginTransaction();
        try {
            $this->orderRepository->add($order);

            foreach ($calculation['items'] as $item) {
                $this->orderItemRepository->add(OrderItem::create(
                    orderId: $orderId,
                    productId: $item['product_id'],
                    productName: $item['product_name'],
                    price: $item['price'],
                    quantity: $item['quantity'],
                ));
            }

            if ($calculation['buyer_profile'] !== null && $calculation['bonus_used'] > 0) {
                $calculation['buyer_profile']->addBonus(-$calculation['bonus_used']);
                $this->bonusRepository->add(BonusTransaction::create(
                    userId: $command->userId,
                    amount: -$calculation['bonus_used'],
                    type: BonusTransactionType::MANUAL_ADJUSTMENT,
                    sourceOrderId: $orderId,
                    comment: 'Списание бонусов за заказ',
                ));
            }

            if ($calculation['promo_code_id'] !== null && $calculation['discount_amount'] > 0) {
                $this->redeemPromoCode($calculation['promo_code_id']);
                $this->connection->insert('promo_code_redemptions', [
                    'promo_code_id'   => $calculation['promo_code_id'],
                    'order_id'        => $orderId,
                    'user_id'         => $command->userId,
                    'discount_amount' => $calculation['discount_amount'],
                    'created_at'      => gmdate('Y-m-d H:i:s'),
                ]);
            }

            $this->cacher->deleteTag('orders');
            $this->flusher->flush();
            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }

        $this->emailNotifier->created($order);

        return [
            'id'              => $orderId,
            'user_id'         => $command->userId,
            'subtotal'        => $calculation['subtotal'],
            'delivery_method' => $calculation['delivery_method'],
            'delivery_cost'   => $calculation['delivery_cost'],
            'discount_amount' => $calculation['discount_amount'],
            'bonus_used'      => $calculation['bonus_used'],
            'total'           => $calculation['total'],
        ];
    }

    private function resolveReferredBy(CreateOrderCommand $command): ?string
    {
        if (!$this->settings->bool('referral_enabled')) {
            return null;
        }

        $referredBy = trim((string)$command->referredBy);
        if ($referredBy !== '') {
            return $referredBy;
        }

        $profile = $this->profileRepository->findByUserId($command->userId);
        if ($profile === null || $profile->referredByUserId === null) {
            return null;
        }

        $referrerProfile = $this->profileRepository->findByUserId($profile->referredByUserId);

        return $referrerProfile?->referralCode ?: (string)$profile->referredByUserId;
    }

    /**
     * @return array{
     *     subtotal: int,
     *     delivery_method: string,
     *     delivery_cost: int,
     *     discount_amount: int,
     *     promo_code: string|null,
     *     promo_code_id: int|null,
     *     bonus_used: int,
     *     total: int,
     *     buyer_profile: UserProfile|null,
     *     items: list<array{product_id: int, product_name: string, price: int, quantity: int}>
     * }
     * @throws Exception
     */
    private function calculate(CreateOrderCommand $command): array
    {
        $items = $this->normalizeItems($command->items);
        $products = $this->productsById(array_keys($items));
        $calculatedItems = [];
        $subtotal = 0;

        foreach ($items as $productId => $quantity) {
            if (!isset($products[$productId])) {
                throw new DomainExceptionModule(
                    module: 'order',
                    message: 'error.order_product_not_available',
                    code: 21,
                    payload: ['product_id' => $productId],
                );
            }

            $price = $products[$productId]['price'];
            $subtotal += $price * $quantity;
            $calculatedItems[] = [
                'product_id'   => $productId,
                'product_name' => $products[$productId]['name'],
                'price'        => $price,
                'quantity'     => $quantity,
            ];
        }

        $deliveryMethod = $this->normalizeDeliveryMethod($command->deliveryMethod);
        $deliveryCost = $this->deliveryCost($subtotal, $deliveryMethod);
        $promo = $this->promoDiscount($command->promoCode, $subtotal);
        $baseTotal = max(0, $subtotal + $deliveryCost - $promo['discount_amount']);
        $buyerProfile = $this->profileRepository->findByUserId($command->userId);
        $bonusUsed = $this->bonusUsed($command, $baseTotal, $buyerProfile);

        return [
            'subtotal'        => $subtotal,
            'delivery_method' => $deliveryMethod,
            'delivery_cost'   => $deliveryCost,
            'discount_amount' => $promo['discount_amount'],
            'promo_code'      => $promo['code'],
            'promo_code_id'   => $promo['id'],
            'bonus_used'      => $bonusUsed,
            'total'           => max(0, $baseTotal - $bonusUsed),
            'buyer_profile'   => $buyerProfile,
            'items'           => $calculatedItems,
        ];
    }

    /**
     * @param list<array{productId?: int|string, product_id?: int|string, quantity?: int|string}> $items
     * @return array<int, int>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $productId = (int)($item['productId'] ?? $item['product_id'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $normalized[$productId] = min(99, ($normalized[$productId] ?? 0) + $quantity);
        }

        if ($normalized === []) {
            throw new DomainExceptionModule(
                module: 'order',
                message: 'error.order_items_required',
                code: 22,
            );
        }

        return $normalized;
    }

    /**
     * @param list<int> $productIds
     * @return array<int, array{name: string, price: int}>
     * @throws Exception
     */
    private function productsById(array $productIds): array
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('p.id', 'p.name', 'p.price')
            ->from('products', 'p')
            ->where('p.id IN (:ids)')
            ->andWhere('p.deleted_at IS NULL')
            ->andWhere('p.is_active = 1')
            ->setParameter('ids', $productIds, ArrayParameterType::INTEGER)
            ->executeQuery()
            ->fetchAllAssociative();

        $products = [];
        foreach ($rows as $row) {
            $products[(int)$row['id']] = [
                'name'  => (string)$row['name'],
                'price' => max(0, (int)$row['price']),
            ];
        }

        return $products;
    }

    private function normalizeDeliveryMethod(string $deliveryMethod): string
    {
        $method = trim($deliveryMethod);

        return \in_array($method, ['cdek', 'post'], true) ? $method : 'cdek';
    }

    private function normalizePaymentMethod(string $paymentMethod): string
    {
        $method = trim($paymentMethod);

        if ($method === '') {
            return 'card';
        }

        return mb_substr($method, 0, 20);
    }

    private function deliveryCost(int $subtotal, string $deliveryMethod): int
    {
        if ($subtotal >= $this->settings->int('free_delivery_threshold', 3000)) {
            return 0;
        }

        return match ($deliveryMethod) {
            'post'  => max(0, $this->settings->int('post_delivery_price', 250)),
            default => max(0, $this->settings->int('cdek_delivery_price', 350)),
        };
    }

    /**
     * @return array{id: int|null, code: string|null, discount_amount: int}
     * @throws Exception
     */
    private function promoDiscount(?string $promoCode, int $subtotal): array
    {
        $code = mb_strtoupper(trim((string)$promoCode));
        if ($code === '' || !$this->settings->bool('promo_codes_enabled')) {
            return ['id' => null, 'code' => null, 'discount_amount' => 0];
        }

        $row = $this->connection->fetchAssociative(
            'SELECT id, code, type, value
            FROM promo_codes
            WHERE code = :code
              AND is_active = 1
              AND min_order_total <= :subtotal
              AND (starts_at IS NULL OR starts_at <= UTC_TIMESTAMP())
              AND (ends_at IS NULL OR ends_at >= UTC_TIMESTAMP())
              AND (usage_limit IS NULL OR used_count < usage_limit)
            LIMIT 1',
            ['code' => $code, 'subtotal' => $subtotal],
        );

        if ($row === false) {
            return ['id' => null, 'code' => null, 'discount_amount' => 0];
        }

        $value = max(0, (int)$row['value']);
        $discount = match ((string)$row['type']) {
            'percent' => (int)floor($subtotal * min($value, 100) / 100),
            default   => min($value, $subtotal),
        };

        return [
            'id'              => (int)$row['id'],
            'code'            => (string)$row['code'],
            'discount_amount' => min($discount, $subtotal),
        ];
    }

    /**
     * @throws Exception
     */
    private function redeemPromoCode(int $promoCodeId): void
    {
        $updated = $this->connection->executeStatement(
            'UPDATE promo_codes
            SET used_count = used_count + 1, updated_at = UTC_TIMESTAMP()
            WHERE id = :id
              AND is_active = 1
              AND (starts_at IS NULL OR starts_at <= UTC_TIMESTAMP())
              AND (ends_at IS NULL OR ends_at >= UTC_TIMESTAMP())
              AND (usage_limit IS NULL OR used_count < usage_limit)',
            ['id' => $promoCodeId],
        );

        if ($updated !== 1) {
            throw new DomainExceptionModule(
                module: 'order',
                message: 'error.promo_code_unavailable',
                code: 23,
                status: 422,
            );
        }
    }

    private function bonusUsed(CreateOrderCommand $command, int $baseTotal, ?UserProfile $profile): int
    {
        if (!$command->useBonuses || !$this->settings->bool('order_bonus_enabled') || $profile === null) {
            return 0;
        }

        $limit = (int)floor($baseTotal * max(0, min(100, $this->settings->int('order_bonus_spend_limit_percent', 30))) / 100);

        return min(max(0, $profile->bonusBalance), $baseTotal, $limit);
    }
}
