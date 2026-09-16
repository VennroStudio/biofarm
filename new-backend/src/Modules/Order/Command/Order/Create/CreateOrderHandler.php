<?php

declare(strict_types=1);

namespace App\Modules\Order\Command\Order\Create;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Components\Id\ReadableIdGenerator;
use App\Components\Setting\SiteSettings;
use App\Modules\Bonus\Entity\BonusTransaction\BonusTransactionRepository;
use App\Modules\Order\Entity\Order\Order;
use App\Modules\Order\Entity\Order\OrderRepository;
use App\Modules\Order\Entity\OrderItem\OrderItem;
use App\Modules\Order\Entity\OrderItem\OrderItemRepository;
use App\Modules\Order\Permission\OrderPermission;
use App\Modules\Order\Service\Bitrix24OrderSyncer;
use App\Modules\Order\Service\OrderEmailNotifier;
use App\Modules\Order\Service\OrderPermissionService;
use App\Modules\PartnerOffer\Service\PartnerPromoService;
use App\Modules\Program\Service\ProgramService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use App\Modules\User\Entity\UserProfile\UserProfile;
use App\Modules\User\Entity\UserProfile\UserProfileRepository;
use DateMalformedStringException;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Random\RandomException;

final readonly class CreateOrderHandler
{
    public function __construct(
        private ProgramService $program,
        private PartnerPromoService $partnerPromo,
        private OrderRepository $orderRepository,
        private OrderItemRepository $orderItemRepository,
        private ReadableIdGenerator $idGenerator,
        private OrderPermissionService $permissionService,
        private UserProfileRepository $profileRepository,
        private BonusTransactionRepository $bonusRepository,
        private SiteSettings $settings,
        private OrderEmailNotifier $emailNotifier,
        private Bitrix24OrderSyncer $bitrix24OrderSyncer,
        private Connection $connection,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @return array{id: string, user_id: int|null, subtotal: int, delivery_method: string, delivery_cost: int, discount_amount: int, bonus_used: int, total: int}
     * @throws DateMalformedStringException
     * @throws Exception
     * @throws RandomException
     */
    public function handle(CreateOrderCommand $command, bool $notify = true): array
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
        $shippingAddress = $this->normalizeShippingAddress($command->shippingAddress);

        $order = Order::create(
            id: $orderId,
            userId: $command->userId,
            total: $calculation['total'],
            subtotal: $calculation['subtotal'],
            deliveryMethod: $calculation['delivery_method'],
            deliveryCost: $calculation['delivery_cost'],
            discountAmount: $calculation['discount_amount'],
            promoCode: $calculation['promo_code'],
            shippingAddress: $shippingAddress,
            paymentMethod: $this->normalizePaymentMethod($command->paymentMethod),
            bonusUsed: $calculation['bonus_used'],
            status: $command->status,
            paymentStatus: $command->paymentStatus,
            referredBy: $this->resolveReferredBy($command),
        );

        $this->program->atomic(function () use ($order, $orderId, $command, $calculation): void {
            $this->partnerPromo->validateCheckout($calculation['promo_code']);
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
            $this->program->captureOrder($orderId);
            if ($order->paymentStatus === 'completed') {
                $this->program->settleOrder($orderId);
            }
        });

        if ($notify) {
            $this->notifyCreated($orderId);
        }

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

    public function notifyCreated(string $orderId): void
    {
        $order = $this->orderRepository->getById($orderId);
        $items = $this->connection->fetchAllAssociative('SELECT product_id,product_name,price,quantity FROM order_items WHERE order_id=?', [$orderId]);
        $this->emailNotifier->created($order);
        $this->bitrix24OrderSyncer->created($order, $items);
    }

    private function resolveReferredBy(CreateOrderCommand $command): ?string
    {
        if (!$this->settings->bool('referral_enabled')) {
            return null;
        }

        $profile = $command->userId === null ? null : $this->profileRepository->findByUserId($command->userId);
        if ($profile?->isPartner) {
            return null;
        }
        if ($profile?->referredByUserId !== null) {
            $referrerProfile = $this->profileRepository->findByUserId($profile->referredByUserId);
            return $referrerProfile?->referralCode ?: (string)$profile->referredByUserId;
        }
        $code = trim((string)$command->referredBy);
        $referrer = ctype_digit($code) ? $this->profileRepository->findByUserId((int)$code) : $this->profileRepository->findByReferralCode($code);
        return $referrer === null ? null : ($referrer->referralCode ?: (string)$referrer->userId);
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
        $buyerProfile = $command->userId !== null
            ? $this->profileRepository->findByUserId($command->userId)
            : null;
        $bonusUsed = $this->bonusUsed($command, max(0, $subtotal - $promo['discount_amount']), $buyerProfile);

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

        if (!\in_array($method, ['card', 'sbp'], true)) {
            throw new DomainExceptionModule(
                module: 'order',
                message: 'error.order_payment_method_invalid',
                code: 25,
                status: 422,
            );
        }

        return $method;
    }

    /**
     * @param array<string, mixed> $shippingAddress
     * @return array{name: string, phone: string, email: string, city: string, address: string, postalCode: string, postal_code: string, comment: string|null}
     */
    private function normalizeShippingAddress(array $shippingAddress): array
    {
        $email = $this->requiredShippingValue($shippingAddress, ['email']);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw $this->invalidShippingAddress();
        }

        $postalCode = $this->requiredShippingValue($shippingAddress, ['postalCode', 'postal_code']);

        return [
            'name'        => $this->requiredShippingValue($shippingAddress, ['name']),
            'phone'       => $this->requiredShippingValue($shippingAddress, ['phone']),
            'email'       => $email,
            'city'        => $this->requiredShippingValue($shippingAddress, ['city']),
            'address'     => $this->requiredShippingValue($shippingAddress, ['address']),
            'postalCode'  => $postalCode,
            'postal_code' => $postalCode,
            'comment'     => $this->optionalShippingValue($shippingAddress, ['comment']),
        ];
    }

    /**
     * @param array<string, mixed> $source
     * @param list<string> $keys
     */
    private function requiredShippingValue(array $source, array $keys): string
    {
        $value = $this->optionalShippingValue($source, $keys);
        if ($value === null) {
            throw $this->invalidShippingAddress();
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $source
     * @param list<string> $keys
     */
    private function optionalShippingValue(array $source, array $keys): ?string
    {
        foreach ($keys as $key) {
            $raw = $source[$key] ?? null;

            if (!\is_scalar($raw)) {
                continue;
            }

            $value = trim((string)$raw);
            if ($value !== '') {
                return mb_substr($value, 0, 500);
            }
        }

        return null;
    }

    private function invalidShippingAddress(): DomainExceptionModule
    {
        return new DomainExceptionModule(
            module: 'order',
            message: 'error.order_shipping_address_invalid',
            code: 24,
            status: 422,
        );
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

        return min(intdiv($this->program->shoppingAvailable($profile->userId), 100), $baseTotal, $limit);
    }
}
