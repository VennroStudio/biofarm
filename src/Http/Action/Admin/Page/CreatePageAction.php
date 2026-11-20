<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Page;

use App\Components\Http\Response\JsonDataResponse;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Modules\Page\Command\Page\Create\CreatePageCommand;
use App\Modules\Page\Command\Page\Create\CreatePageHandler;
use DateMalformedStringException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

final readonly class CreatePageAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Validator $validator,
        private CreatePageHandler $handler,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws ExceptionInterface
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $command = $this->denormalizer->denormalize((array)$request->getParsedBody(), CreatePageCommand::class);
        $this->validator->validate($command);
        $id = $this->handler->handle($command);

        return new JsonDataResponse(['id' => $id], 201);
    }
}
