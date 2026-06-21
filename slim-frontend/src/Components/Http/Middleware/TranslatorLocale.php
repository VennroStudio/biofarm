<?php

declare(strict_types=1);

namespace App\Components\Http\Middleware;

use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Translation\Translator;

final readonly class TranslatorLocale implements MiddlewareInterface
{
    public function __construct(
        private Translator $translator,
    ) {}

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Accept-Language');

        if ($header !== '') {
            $locale = $this->parseLocale($header);

            if ($locale !== null) {
                $this->translator->setLocale($locale);
            }
        }

        return $handler->handle($request);
    }

    private function parseLocale(string $header): ?string
    {
        $primary = strtok($header, ',');

        if ($primary === false) {
            return null;
        }

        $locale = strtok(trim($primary), ';');

        if ($locale === false) {
            return null;
        }

        // ru-RU → ru
        return strtolower(strtok($locale, '-') ?: $locale);
    }
}
