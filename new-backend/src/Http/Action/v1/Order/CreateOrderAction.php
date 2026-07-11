<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Order;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Http\Response\JsonErrorResponse;
use App\Components\Serializer\Denormalizer;
use App\Components\Setting\SiteSettings;
use App\Components\Validator\Validator;
use App\Modules\Order\Command\Order\Create\CreateOrderCommand;
use App\Modules\Order\Command\Order\Create\CreateOrderHandler;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use DateMalformedStringException;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Random\RandomException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[OA\Post(path: '/orders/create', summary: 'Создать заказ', security: [['bearerAuth' => []]], tags: ['Orders'])]
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
     * @throws ExceptionInterface
     * @throws RandomException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = RequestIdentity::get($request);
        $payload = (array)$request->getParsedBody();

        if ($identity->role === UserRole::USER && !$this->settings->bool('cart_enabled')) {
            return new JsonErrorResponse(1, 'cart_disabled', status: 403);
        }

        if ($identity->role === UserRole::USER) {
            $payload['userId'] = $identity->id;
        }

        $payload = array_merge($payload, [
            'currentUserId'   => $identity->id,
            'currentUserRole' => $identity->role->value,
        ]);

        $command = $this->denormalizer->denormalize($payload, CreateOrderCommand::class);
        $this->validator->validate($command);
        $orderId = $this->handler->handle($command);

        return new JsonDataResponse([
            'id'      => $orderId,
            'user_id' => $command->userId,
            'total'   => $command->total,
        ], 201);
    }
}
