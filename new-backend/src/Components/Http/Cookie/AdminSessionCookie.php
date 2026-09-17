<?php

declare(strict_types=1);

namespace App\Components\Http\Cookie;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AdminSessionCookie
{
    private const string NAME = 'biofarm_admin_refresh_token';

    public function __construct(private bool $secure = true, private int $ttl = 2592000) {}

    public function read(ServerRequestInterface $request): string
    {
        $value = $request->getCookieParams()[self::NAME] ?? '';
        return \is_string($value) ? $value : '';
    }

    public function apply(ResponseInterface $response, string $token): ResponseInterface
    {
        return $this->write($response, $token, $this->ttl);
    }

    public function discard(ResponseInterface $response): ResponseInterface
    {
        return $this->write($response, '', 0);
    }

    private function write(ResponseInterface $response, string $token, int $ttl): ResponseInterface
    {
        // Host-only and a separate name prevent site logins from replacing the admin session.
        $cookie = self::NAME . '=' . rawurlencode($token)
            . '; Path=/admin/api/auth; HttpOnly; SameSite=Lax; Max-Age=' . $ttl
            . '; Expires=' . gmdate('D, d M Y H:i:s \G\M\T', $ttl ? time() + $ttl : 0);
        if ($this->secure) $cookie .= '; Secure';
        return $response->withAddedHeader('Set-Cookie', $cookie);
    }
}
