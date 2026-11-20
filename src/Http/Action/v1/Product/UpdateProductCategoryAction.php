<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Product;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Modules\Product\Command\ProductCategory\Update\UpdateProductCategoryCommand;
use App\Modules\Product\Command\ProductCategory\Update\UpdateProductCategoryHandler;
use DateMalformedStringException;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[OA\Patch(path: '/product-categories/update/{id}', summary: 'Обновить категорию товара', security: [['bearerAuth' => []]], tags: ['Product'])]
final readonly class UpdateProductCategoryAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Validator $validator,
        private UpdateProductCategoryHandler $handler,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws ExceptionInterface
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = RequestIdentity::get($request);
        $payload = array_merge((array)$request->getParsedBody(), [
            'categoryId' => Route::getArgumentToInt($request, 'id'),
        ]);

        $payload['currentUserId'] = $identity->id;
        $payload['currentUserRole'] = $identity->role->value;

        $command = $this->denormalizer->denormalize($payload, UpdateProductCategoryCommand::class);
        $this->validator->validate($command);
        $this->handler->handle($command);

        return new JsonDataSuccessResponse(1, 200);
    }
}
