<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Product;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Modules\Product\Command\ProductCategory\Delete\DeleteProductCategoryCommand;
use App\Modules\Product\Command\ProductCategory\Delete\DeleteProductCategoryHandler;
use DateMalformedStringException;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[OA\Delete(path: '/product-categories/delete/{id}', summary: 'Удалить категорию товара', security: [['bearerAuth' => []]], tags: ['Product'])]
final readonly class DeleteProductCategoryAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Validator $validator,
        private DeleteProductCategoryHandler $handler,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws ExceptionInterface
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = RequestIdentity::get($request);
        $payload = [
            'categoryId' => Route::getArgumentToInt($request, 'id'),
        ];

        $payload['currentUserId'] = $identity->id;
        $payload['currentUserRole'] = $identity->role->value;

        $command = $this->denormalizer->denormalize($payload, DeleteProductCategoryCommand::class);
        $this->validator->validate($command);
        $this->handler->handle($command);

        return new JsonDataSuccessResponse(1, 200);
    }
}
