<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Order;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Serializer\Denormalizer;
use App\Components\Setting\SiteSettings;
use App\Components\Validator\Validator;
use App\Modules\Order\Command\Order\Create\CreateOrderCommand;
use App\Modules\Order\Command\Order\Create\CreateOrderHandler;
use App\Modules\PartnerOffer\Service\OfferService;
use App\Modules\Payment\Service\PaymentService;
use App\Modules\Program\Service\ProgramService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
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
        $created = false;
        $result = $this->program->atomic(function () use ($command, $offerId, $requestKey, $requestId, $requestHash, &$created): array {
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
            $result = $this->handler->handle($command, false);
            $token = $requestKey !== '' ? hash('sha256', 'payment:' . $result['id'] . ':' . $requestKey) : null;
            $result['paymentAccessToken'] = $this->payments->issueAccess($result['id'], $token);
            if ($offerId !== '') {
                $this->offers->recordOrder($offerId, $result['id']);
            }
            if ($requestKey !== '') {
                $savedResult = $result;
                unset($savedResult['paymentAccessToken']);
                $this->connection->insert('checkout_requests', ['id' => $requestId, 'request_hash' => $requestHash, 'order_id' => $result['id'], 'response' => json_encode($savedResult, JSON_THROW_ON_ERROR), 'created_at' => gmdate('Y-m-d H:i:s')]);
            }
            $created = true;
            return $result;
        });
        if ($created) {
            $this->handler->notifyCreated($result['id']);
        }
        return new JsonDataResponse($result, 201);
    }
}
