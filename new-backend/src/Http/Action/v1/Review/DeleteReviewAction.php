<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Review;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Modules\Review\Command\Review\Delete\DeleteReviewCommand;
use App\Modules\Review\Command\Review\Delete\DeleteReviewHandler;
use DateMalformedStringException;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[OA\Delete(path: '/reviews/delete/{id}', summary: 'Удалить отзыв', security: [['bearerAuth' => []]], tags: ['Reviews'])]
final readonly class DeleteReviewAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Validator $validator,
        private DeleteReviewHandler $handler,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws ExceptionInterface
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = RequestIdentity::get($request);
        $command = $this->denormalizer->denormalize([
            'reviewId'        => Route::getArgument($request, 'id'),
            'currentUserId'   => $identity->id,
            'currentUserRole' => $identity->role->value,
        ], DeleteReviewCommand::class);

        $this->validator->validate($command);
        $this->handler->handle($command);

        return new JsonDataSuccessResponse(1, 200);
    }
}
