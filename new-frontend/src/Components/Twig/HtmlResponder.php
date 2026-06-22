<?php

declare(strict_types=1);

namespace App\Components\Twig;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Twig\Environment;

final readonly class HtmlResponder
{
    public function __construct(
        private Environment $twig,
        private ResponseFactoryInterface $responseFactory,
    ) {}

    /**
     * @param array<string, mixed> $context
     */
    public function render(string $template, array $context = [], int $status = 200): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status)
            ->withHeader('Content-Type', 'text/html; charset=UTF-8');
        $response->getBody()->write($this->twig->render($template, $context));

        return $response;
    }
}
