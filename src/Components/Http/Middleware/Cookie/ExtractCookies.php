<?php

declare(strict_types=1);

namespace App\Components\Http\Middleware\Cookie;

use App\Components\Http\Cookie\CookieContext;
use App\Components\Http\Cookie\RequestCookies;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class ExtractCookies implements MiddlewareInterface
{
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookies = $request->getCookieParams();
        $context = new CookieContext();

        $data = [];
        foreach ($context->getDefinition() as $property => $meta) {
            $data[$property] = (string)($cookies[$meta['name']] ?? '');
        }

        $context = new CookieContext(...$data);

        return $handler->handle(RequestCookies::with($request, $context));
    }
}
