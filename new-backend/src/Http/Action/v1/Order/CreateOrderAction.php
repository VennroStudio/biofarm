<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Order;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Modules\Order\Command\Order\Create\CreateOrderCommand;
use App\Modules\Order\Command\Order\Create\CreateOrderHandler;
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
        $payload = array_merge((array)$request->getParsedBody(), [
            'currentUserId'   => $identity->id,
            'currentUserRole' => $identity->role->value,
        ]);

        $command = $this->denormalizer->denormalize($payload, CreateOrderCommand::class);
        $this->validator->validate($command);
        $this->handler->handle($command);

        return new JsonDataSuccessResponse();
    }
}
