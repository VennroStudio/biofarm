<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Blog;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Modules\Blog\Command\BlogPost\Create\CreateBlogPostCommand;
use App\Modules\Blog\Command\BlogPost\Create\CreateBlogPostHandler;
use DateMalformedStringException;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[OA\Post(path: '/blog/create', summary: 'Создать статью блога', security: [['bearerAuth' => []]], tags: ['Blog'])]
final readonly class CreateBlogPostAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Validator $validator,
        private CreateBlogPostHandler $handler,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws ExceptionInterface
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $payload = (array)$request->getParsedBody();
        $identity = RequestIdentity::get($request);

        if (isset($payload['category']) && !isset($payload['categoryId'])) {
            $payload['categoryId'] = $payload['category'];
        }

        $payload['currentUserId'] = $identity->id;
        $payload['currentUserRole'] = $identity->role->value;

        $command = $this->denormalizer->denormalize($payload, CreateBlogPostCommand::class);
        $this->validator->validate($command);
        $this->handler->handle($command);

        return new JsonDataSuccessResponse();
    }
}
