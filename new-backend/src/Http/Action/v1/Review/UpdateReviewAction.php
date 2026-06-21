<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Review;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Modules\Review\Command\Review\Update\UpdateReviewCommand;
use App\Modules\Review\Command\Review\Update\UpdateReviewHandler;
use DateMalformedStringException;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[OA\Patch(path: '/reviews/update/{id}', summary: 'Обновить отзыв', security: [['bearerAuth' => []]], tags: ['Reviews'])]
final readonly class UpdateReviewAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Validator $validator,
        private UpdateReviewHandler $handler,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws ExceptionInterface
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $payload = array_merge((array)$request->getParsedBody(), [
            'reviewId' => Route::getArgument($request, 'id'),
        ]);
        $identity = RequestIdentity::get($request);

        $payload['currentUserId'] = $identity->id;
        $payload['currentUserRole'] = $identity->role->value;

        $command = $this->denormalizer->denormalize($payload, UpdateReviewCommand::class);
        $this->validator->validate($command);
        $this->handler->handle($command);

        return new JsonDataSuccessResponse(1, 200);
    }
}
