<?php

declare(strict_types=1);

namespace App\Components\Http\Cookie;

use Psr\Http\Message\ResponseInterface;

final readonly class CookieManager
{
    public function __construct(
        private string $domain,
        private bool $secure = true,
    ) {}

    public function apply(ResponseInterface $response, CookieContext $context): ResponseInterface
    {
        foreach ($context->getDefinition() as $property => $meta) {
            /** @var string $value */
            $value = $context->{$property};
            if ($value !== '') {
                $response = $this->set(
                    $response,
                    $meta['name'],
                    $value,
                    $meta['ttl'],
                    $meta['httpOnly'] ?? true
                );
            }
        }

        return $response;
    }

    public function discard(ResponseInterface $response, CookieContext $context): ResponseInterface
    {
        foreach ($context->getDefinition() as $meta) {
            $response = $this->delete($response, $meta['name'], $meta['httpOnly'] ?? true);
        }

        return $response;
    }

    public function set(
        ResponseInterface $response,
        string $name,
        string $value,
        int $ttl,
        bool $httpOnly = true,
    ): ResponseInterface {
        $expires = time() + $ttl;
        $date = gmdate('D, d M Y H:i:s \G\M\T', $expires);

        $cookie = \sprintf(
            '%s=%s; Expires=%s; Max-Age=%d; Path=/; SameSite=Lax',
            $name,
            $value,
            $date,
            $ttl
        );

        if ($httpOnly) {
            $cookie .= '; HttpOnly';
        }

        $cookie .= '; Domain=' . $this->domain;

        if ($this->secure) {
            $cookie .= '; Secure';
        }

        return $response->withAddedHeader('Set-Cookie', $cookie);
    }

    public function delete(ResponseInterface $response, string $name, bool $httpOnly = true): ResponseInterface
    {
        $cookie = \sprintf(
            '%s=; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Max-Age=0; Path=/; SameSite=Lax',
            $name
        );

        if ($httpOnly) {
            $cookie .= '; HttpOnly';
        }

        $cookie .= '; Domain=' . $this->domain;

        if ($this->secure) {
            $cookie .= '; Secure';
        }

        return $response->withAddedHeader('Set-Cookie', $cookie);
    }
}
