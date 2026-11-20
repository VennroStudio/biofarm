<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Blog;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Modules\Blog\Command\BlogPost\Update\UpdateBlogPostCommand;
use App\Modules\Blog\Command\BlogPost\Update\UpdateBlogPostHandler;
use DateMalformedStringException;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[OA\Patch(path: '/blog/update/{id}', summary: 'Обновить статью блога', security: [['bearerAuth' => []]], tags: ['Blog'])]
final readonly class UpdateBlogPostAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Validator $validator,
        private UpdateBlogPostHandler $handler,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws ExceptionInterface
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $payload = array_merge((array)$request->getParsedBody(), [
            'postId' => (int)Route::getArgument($request, 'id'),
        ]);
        $identity = RequestIdentity::get($request);

        if (isset($payload['category']) && !isset($payload['categoryId'])) {
            $payload['categoryId'] = $payload['category'];
        }

        $payload['currentUserId'] = $identity->id;
        $payload['currentUserRole'] = $identity->role->value;

        $command = $this->denormalizer->denormalize($payload, UpdateBlogPostCommand::class);
        $this->validator->validate($command);
        $this->handler->handle($command);

        return new JsonDataSuccessResponse(1, 200);
    }
}
