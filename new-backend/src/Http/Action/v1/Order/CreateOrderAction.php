<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Order;

use App\Components\Privacy\ConsentRecorder;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Serializer\Denormalizer;
use App\Components\Setting\SiteSettings;
use App\Components\Validator\Validator;
use App\Modules\Order\Command\Order\Create\CreateOrderCommand;
use App\Modules\Order\Command\Order\Create\CreateOrderHandler;
use App\Modules\Ozon\OzonException;
use App\Modules\Ozon\OzonPickupCheckout;
use App\Modules\PartnerOffer\Service\OfferService;
use App\Modules\Payment\Service\PaymentService;
use App\Modules\Program\Service\ProgramService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use App\Modules\User\Service\Phone\PhoneAccounts;
use DateMalformedStringException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Random\RandomException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[OA\Post(path: '/orders/create', summary: 'Создать заказ', security: [], tags: ['Orders'])]
final readonly class CreateOrderAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Validator $validator,
        private CreateOrderHandler $handler,
        private SiteSettings $settings,
        private PaymentService $payments,
        private OfferService $offers,
        private Connection $connection,
        private ProgramService $program,
        private OzonPickupCheckout $ozonCheckout,
        private PhoneAccounts $phones,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     * @throws ExceptionInterface
     * @throws RandomException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = RequestIdentity::find($request);
        $payload = (array)$request->getParsedBody();
        $consents = new ConsentRecorder($this->connection);
        $requestKey = $request->getHeaderLine('Idempotency-Key');
        if ($requestKey !== '' && !preg_match('/^[a-zA-Z0-9_-]{32,100}$/D', $requestKey)) {
            throw new DomainExceptionModule('order', 'Некорректный ключ оформления заказа.', 40, status: 422);
        }
        $requestId = hash('sha256', ($identity?->id ?? 'guest') . ':' . $requestKey);
        $requestHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
        if ($requestKey !== '') {
            $saved = $this->connection->fetchAssociative('SELECT * FROM checkout_requests WHERE id=?', [$requestId]);
            if ($saved) {
                if (!hash_equals($saved['request_hash'], $requestHash)) {
                    throw new DomainExceptionModule('order', 'Параметры заказа изменились. Начните новое оформление.', 41, status: 409);
                }
                $result = json_decode($saved['response'], true, 512, JSON_THROW_ON_ERROR);
                $result['paymentAccessToken'] = hash('sha256', 'payment:' . $result['id'] . ':' . $requestKey);
                return new JsonDataResponse($result, 201);
            }
        }
        $consents->assertOffer($payload);
        $consents->requirePersonalData($payload, $identity?->id);

        $offerId = trim((string)($payload['offerId'] ?? ''));
        if ($offerId !== '') {
            $offer = $this->offers->read($offerId, false);
            // A QR basket is a fallback source; preserve an earlier valid invitation.
            $code = trim((string)($payload['referredBy'] ?? ''));
            $valid = $code !== '' && $this->connection->fetchOne('SELECT p.user_id FROM user_profiles p JOIN users u ON u.id=p.user_id WHERE (p.referral_code=? OR p.user_id=?) AND u.deleted_at IS NULL AND u.status=1 LIMIT 1', [$code, ctype_digit($code) ? (int)$code : 0]);
            if (!$valid) {
                $payload['referredBy'] = $offer['referralCode'];
            }
        }

        if (!$this->settings->bool('cart_enabled')) {
            throw new DomainExceptionModule(
                module: 'order',
                message: 'error.cart_disabled',
                code: 20,
            );
        }

        if ($identity?->role === UserRole::USER) {
            $payload['userId'] = $identity->id;
            $payload['orderId'] = null;
            $payload['status'] = 'pending';
            $payload['paymentStatus'] = 'pending';
            $payload['payment_status'] = 'pending';
        } elseif ($identity === null) {
            $payload['userId'] = null;
            $payload['orderId'] = null;
            $payload['status'] = 'pending';
            $payload['paymentStatus'] = 'pending';
            $payload['payment_status'] = 'pending';
        }

        $payload = array_merge($payload, [
            'currentUserId'   => $identity?->id ?? 0,
            'currentUserRole' => $identity?->role->value ?? UserRole::USER->value,
        ]);

        $command = $this->denormalizer->denormalize($payload, CreateOrderCommand::class);
        $this->validator->validate($command);
        $ozonQuote = null;
        $deliveryMethod = trim($command->deliveryMethod);
        $live = $deliveryMethod === OzonPickupCheckout::LIVE_PICKUP;
        $testing = $this->settings->bool('testing_enabled');
        $activeMethod = $this->ozonCheckout->method();
        if (($activeMethod !== null || str_starts_with($deliveryMethod, 'ozon-')) && $deliveryMethod !== $activeMethod) {
            throw new DomainExceptionModule('order', 'Выберите доступный способ доставки.', 43, status: 422);
        }
        if ($live && $requestKey === '') {
            throw new DomainExceptionModule('order', 'Обновите страницу и повторите оформление заказа.', 40, status: 422);
        }
        if ($live && !preg_match('/^\s*\S+\s+\S+/u', (string)($command->shippingAddress['name'] ?? ''))) {
            throw new DomainExceptionModule('order', 'Укажите имя и фамилию получателя.', 43, status: 422);
        }
        $requiresPhone = !$testing && ($live || $this->phones->checkoutEnabled());
        $phoneInput = (string)($command->shippingAddress['phone'] ?? '');
        $verifiedPhone = $requiresPhone ? $this->phones->checkoutPhone($identity, $phoneInput) : null;
        if ($live) {
            try {
                $ozonQuote = $this->ozonCheckout->prepare(
                    $command->items, $verifiedPhone, $identity->id, $payload['ozonPickupPointId'] ?? null,
                );
                $fingerprint = $payload['ozonQuote'] ?? null;
                if (!is_string($fingerprint) || !hash_equals($this->ozonCheckout->fingerprint($ozonQuote), $fingerprint)) {
                    throw new OzonException('Условия доставки изменились. Проверьте новую стоимость и срок.');
                }
            } catch (OzonException $exception) {
                throw new DomainExceptionModule('order', $exception->getCustomerMessage(), 43, status: 422, previous: $exception);
            }
        }
        $created = false;
        $result = $this->program->atomic(function () use ($command, $offerId, $requestKey, $requestId, $requestHash, $consents, $request, $payload, $ozonQuote, $live, $testing, $requiresPhone, $identity, $phoneInput, &$created): array {
            if ($requestKey !== '') {
                $saved = $this->connection->fetchAssociative('SELECT * FROM checkout_requests WHERE id=?', [$requestId]);
                if ($saved) {
                    if (!hash_equals($saved['request_hash'], $requestHash)) {
                        throw new DomainExceptionModule('order', 'Параметры заказа изменились. Начните новое оформление.', 41, status: 409);
                    }
                    $result = json_decode($saved['response'], true, 512, JSON_THROW_ON_ERROR);
                    $result['paymentAccessToken'] = hash('sha256', 'payment:' . $result['id'] . ':' . $requestKey);
                    return $result;
                }
            }
            $newConsent = $consents->requirePersonalData($payload, $identity?->id, lock: true);
            if ($requiresPhone) {
                $this->phones->checkoutPhone($identity, $phoneInput, lock: true);
            }
            $result = $this->handler->handle(
                $command,
                false,
                $ozonQuote['shippingDestination'] ?? null,
                $ozonQuote['deliveryCostMinor'] ?? null,
                rememberAddress: ($payload['rememberAddress'] ?? false) === true && $identity !== null && $command->userId === $identity->id,
            );
            $offerEvidence = ['action' => 'checkout_submit'];
            if ($ozonQuote !== null) {
                $offerEvidence['ozonTerms'] = ConsentRecorder::OZON_TERMS;
            }
            $consents->record($request, 'order', $result['id'], 'offer', true, $offerEvidence);
            if ($newConsent) {
                $consents->record($request, $identity === null ? 'order' : 'user', $identity === null ? $result['id'] : (string)$identity->id, 'personal_data', true);
            }
            $token = $requestKey !== '' ? hash('sha256', 'payment:' . $result['id'] . ':' . $requestKey) : null;
            $result['paymentAccessToken'] = $this->payments->issueAccess($result['id'], $token);
            if ($offerId !== '') {
                $this->offers->recordOrder($offerId, $result['id']);
            }
            if ($live && (float)$result['total'] <= 0) {
                throw new DomainExceptionModule('order', 'Для доставки Ozon нужна оплата. Уменьшите сумму списываемых бонусов.', 44, status: 422);
            }
            if ($testing) {
                if (!$this->payments->completeCheckoutForTesting($result['id'])) {
                    throw new DomainExceptionModule('order', 'Режим оформления изменился. Обновите страницу.', 44, status: 409);
                }
                $result['payment_status'] = 'completed';
            } elseif (!$live && $this->payments->completeCheckoutWithoutCash($result['id'])) {
                $result['payment_status'] = 'completed';
            }
            if ($ozonQuote !== null) {
                try {
                    $this->ozonCheckout->saveOrder($result['id'], $ozonQuote);
                } catch (OzonException $exception) {
                    throw new DomainExceptionModule('order', $exception->getCustomerMessage(), 43, status: 422, previous: $exception);
                }
            }
            if ($requestKey !== '') {
                $savedResult = $result;
                unset($savedResult['paymentAccessToken']);
                $this->connection->insert('checkout_requests', ['id' => $requestId, 'request_hash' => $requestHash, 'order_id' => $result['id'], 'response' => json_encode($savedResult, JSON_THROW_ON_ERROR), 'created_at' => gmdate('Y-m-d H:i:s')]);
            }
            $created = true;
            return $result;
        });
        if ($created && !$testing) {
            $this->handler->notifyCreated($result['id']);
        }
        return new JsonDataResponse($result, 201);
    }
}
