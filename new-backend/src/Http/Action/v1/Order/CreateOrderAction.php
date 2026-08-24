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
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use DateMalformedStringException;
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
        return new JsonDataResponse($this->handler->handle($command), 201);
    }
}
