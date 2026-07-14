<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Page;

use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Modules\Page\Command\Page\Update\UpdatePageCommand;
use App\Modules\Page\Command\Page\Update\UpdatePageHandler;
use DateMalformedStringException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

final readonly class UpdatePageAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Validator $validator,
        private UpdatePageHandler $handler,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws ExceptionInterface
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $payload = array_merge((array)$request->getParsedBody(), [
            'pageId' => Route::getArgumentToInt($request, 'id'),
        ]);

        $command = $this->denormalizer->denormalize($payload, UpdatePageCommand::class);
        $this->validator->validate($command);
        $this->handler->handle($command);

        return new JsonDataSuccessResponse(1, 200);
    }
}
