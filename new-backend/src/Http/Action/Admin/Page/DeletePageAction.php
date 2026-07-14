<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Page;

use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Modules\Page\Command\Page\Delete\DeletePageCommand;
use App\Modules\Page\Command\Page\Delete\DeletePageHandler;
use DateMalformedStringException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

final readonly class DeletePageAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Validator $validator,
        private DeletePageHandler $handler,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws ExceptionInterface
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $command = $this->denormalizer->denormalize([
            'pageId' => Route::getArgumentToInt($request, 'id'),
        ], DeletePageCommand::class);

        $this->validator->validate($command);
        $this->handler->handle($command);

        return new JsonDataSuccessResponse(1, 200);
    }
}
