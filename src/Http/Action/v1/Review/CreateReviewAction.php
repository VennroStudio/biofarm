<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Review;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Modules\Review\Command\Review\Create\CreateReviewCommand;
use App\Modules\Review\Command\Review\Create\CreateReviewHandler;
use DateMalformedStringException;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Random\RandomException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[OA\Post(path: '/reviews/create', summary: 'Создать отзыв', security: [['bearerAuth' => []]], tags: ['Reviews'])]
final readonly class CreateReviewAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Validator $validator,
        private CreateReviewHandler $handler,
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

        $command = $this->denormalizer->denormalize(
            array_merge((array)$request->getParsedBody(), [
                'currentUserId'   => $identity->id,
                'currentUserRole' => $identity->role->value,
            ]),
            CreateReviewCommand::class,
        );

        $this->validator->validate($command);
        $this->handler->handle($command);

        return new JsonDataSuccessResponse();
    }
}
