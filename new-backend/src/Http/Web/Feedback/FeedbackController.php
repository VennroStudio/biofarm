<?php

declare(strict_types=1);

namespace App\Http\Web\Feedback;

use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class FeedbackController implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        unset($request);

        return $this->responseFactory->createResponse(303)
            ->withHeader('Location', '/#partner');
    }
}
