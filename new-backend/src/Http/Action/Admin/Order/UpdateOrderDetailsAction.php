<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Order;

use App\Components\Cacher\Cacher;
use App\Components\Flusher\FlusherInterface;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use App\Modules\Order\Entity\Order\OrderRepository;
use App\Modules\Order\Service\OrderBonusApplier;
use App\Modules\Order\Service\OrderEmailNotifier;
use App\Modules\Order\Service\OrderStatusGuard;
use App\Modules\Program\Service\ProgramService;
use DateMalformedStringException;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class UpdateOrderDetailsAction implements RequestHandlerInterface
{
    public function __construct(
        private ProgramService $program,
        private OrderRepository $repository,
        private OrderBonusApplier $bonusApplier,
        private OrderEmailNotifier $emailNotifier,
        private OrderStatusGuard $statusGuard,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $order = $this->program->atomic(function () use ($request) {
            $order = $this->repository->getById(Route::getArgument($request, 'id'));
            $payload = (array)$request->getParsedBody();

            $subtotal = $this->intPayload($payload, ['subtotal'], $order->subtotal);
            $deliveryCost = $this->intPayload($payload, ['deliveryCost', 'delivery_cost'], $order->deliveryCost);
            $discountAmount = $this->intPayload($payload, ['discountAmount', 'discount_amount'], $order->discountAmount);
            $bonusUsed = $this->intPayload($payload, ['bonusUsed', 'bonus_used'], $order->bonusUsed);
            $amountChanged = $subtotal !== $order->subtotal || $deliveryCost !== $order->deliveryCost || $discountAmount !== $order->discountAmount || $bonusUsed !== $order->bonusUsed;
            $total = $this->intPayload($payload, ['total'], $amountChanged ? max(0, $subtotal + $deliveryCost - $discountAmount - $bonusUsed) : $order->total);
            $userId = $this->nullableIntPayload($payload, ['userId', 'user_id'], $order->userId);
            $promoCode = $this->nullableStringPayload($payload, ['promoCode', 'promo_code'], $order->promoCode);
            $referredBy = $this->nullableStringPayload($payload, ['referredBy', 'referred_by'], $order->referredBy);
            $bonusEarned = $this->intPayload($payload, ['bonusEarned', 'bonus_earned'], $order->bonusEarned);
            $paymentMethod = $this->stringPayload($payload, ['paymentMethod', 'payment_method'], $order->paymentMethod);
            $status = $this->statusGuard->orderStatus($this->stringPayload($payload, ['status'], $order->status));
            $paymentStatus = $this->statusGuard->paymentStatus($this->stringPayload($payload, ['paymentStatus', 'payment_status'], $order->paymentStatus));
            $this->statusGuard->adminFinancialTerms($order, compact('total', 'subtotal', 'deliveryCost', 'discountAmount', 'bonusUsed', 'userId', 'promoCode', 'referredBy', 'bonusEarned', 'paymentMethod'), $payload);
            $this->statusGuard->adminTransition($order, $status, $paymentStatus);

            $order->edit(
                userId: $userId,
                status: $status,
                paymentStatus: $paymentStatus,
                total: $total,
                subtotal: $subtotal,
                deliveryMethod: $this->nullableStringPayload($payload, ['deliveryMethod', 'delivery_method'], $order->deliveryMethod),
                deliveryCost: $deliveryCost,
                discountAmount: $discountAmount,
                promoCode: $promoCode,
                bonusUsed: $bonusUsed,
                bonusEarned: $bonusEarned,
                shippingAddress: $this->shippingAddress($payload, $order->shippingAddress),
                paymentMethod: $paymentMethod,
                trackingNumber: $this->nullableStringPayload($payload, ['trackingNumber', 'tracking_number'], $order->trackingNumber),
                referredBy: $referredBy,
            );

            $this->bonusApplier->apply($order);

            $this->cacher->deleteTag('orders');
            $this->cacher->delete('order_by_id_' . $order->id);
            $this->flusher->flush();
            return $order;
        });
        $this->emailNotifier->updated($order);

        return new JsonDataSuccessResponse(1, 200);
    }

    /**
     * @param array<array-key, mixed> $payload
     * @param list<string> $keys
     */
    private function intPayload(array $payload, array $keys, int $fallback): int
    {
        foreach ($keys as $key) {
            if (\array_key_exists($key, $payload)) {
                return max(0, (int)$payload[$key]);
            }
        }

        return max(0, $fallback);
    }

    /**
     * @param array<array-key, mixed> $payload
     * @param list<string> $keys
     */
    private function nullableIntPayload(array $payload, array $keys, ?int $fallback): ?int
    {
        foreach ($keys as $key) {
            if (\array_key_exists($key, $payload)) {
                return $payload[$key] === null || $payload[$key] === '' ? null : max(0, (int)$payload[$key]);
            }
        }

        return $fallback !== null ? max(0, $fallback) : null;
    }

    /**
     * @param array<array-key, mixed> $payload
     * @param list<string> $keys
     */
    private function stringPayload(array $payload, array $keys, string $fallback): string
    {
        foreach ($keys as $key) {
            if (\array_key_exists($key, $payload)) {
                $value = trim((string)$payload[$key]);

                return $value !== '' ? $value : $fallback;
            }
        }

        return $fallback;
    }

    /**
     * @param array<array-key, mixed> $payload
     * @param list<string> $keys
     */
    private function nullableStringPayload(array $payload, array $keys, ?string $fallback): ?string
    {
        foreach ($keys as $key) {
            if (\array_key_exists($key, $payload)) {
                $value = trim((string)$payload[$key]);

                return $value !== '' ? $value : null;
            }
        }

        return $fallback;
    }

    /**
     * @param array<array-key, mixed> $payload
     * @param array{
     *     name?: string|null,
     *     phone?: string|null,
     *     email?: string|null,
     *     city?: string|null,
     *     address?: string|null,
     *     postal_code?: string|null,
     *     postalCode?: string|null
     * } $fallback
     * @return array{
     *     name?: string|null,
     *     phone?: string|null,
     *     email?: string|null,
     *     city?: string|null,
     *     address?: string|null,
     *     postal_code?: string|null,
     *     postalCode?: string|null
     * }
     */
    private function shippingAddress(array $payload, array $fallback): array
    {
        $source = [];
        $raw = $payload['shippingAddress'] ?? $payload['shipping_address'] ?? null;
        if (\is_array($raw)) {
            $source = $raw;
        }

        foreach (['name', 'phone', 'email', 'city', 'address', 'postal_code', 'postalCode'] as $key) {
            if (\array_key_exists($key, $payload)) {
                $source[$key] = $payload[$key];
            }
        }

        $address = $fallback;
        foreach (['name', 'phone', 'email', 'city', 'address', 'postal_code', 'postalCode'] as $key) {
            if (\array_key_exists($key, $source)) {
                $value = \is_scalar($source[$key]) ? trim((string)$source[$key]) : '';
                $address[$key] = $value !== '' ? $value : null;
            }
        }

        return $address;
    }
}
