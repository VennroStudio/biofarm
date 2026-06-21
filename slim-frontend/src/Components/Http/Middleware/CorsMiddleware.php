<?php

declare(strict_types=1);

namespace App\Components\Http\Middleware;

use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class CorsMiddleware implements MiddlewareInterface
{
    private const string ALLOWED_METHODS = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';
    private const string ALLOWED_HEADERS = 'Content-Type, Authorization, Accept, X-Requested-With';
    private const int PREFLIGHT_MAX_AGE = 86400;

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');

        if ($origin === '') {
            return $handler->handle($request);
        }

        if (!$this->isAllowedOrigin($origin)) {
            return $handler->handle($request);
        }

        if ($request->getMethod() === 'OPTIONS') {
            return $this->createPreflightResponse($origin);
        }

        $response = $handler->handle($request);

        return $this->addCorsHeaders($response, $origin);
    }

    private function isAllowedOrigin(string $origin): bool
    {
        $domains = ['wowbanner.local'];

        foreach ($domains as $domain) {
            $escaped = preg_quote($domain, '~');
            $pattern = '~^https?://(.+\.)?' . $escaped . '$~i';

            if (preg_match($pattern, $origin) === 1) {
                return true;
            }
        }

        return false;
    }

    private function createPreflightResponse(string $origin): ResponseInterface
    {
        $response = new Response(204);

        return $this->addCorsHeaders($response, $origin)
            ->withHeader('Access-Control-Max-Age', (string)self::PREFLIGHT_MAX_AGE)
            ->withHeader('Content-Length', '0');
    }

    private function addCorsHeaders(ResponseInterface $response, string $origin): ResponseInterface
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Methods', self::ALLOWED_METHODS)
            ->withHeader('Access-Control-Allow-Headers', self::ALLOWED_HEADERS)
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }
}
