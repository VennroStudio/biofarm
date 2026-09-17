<?php

declare(strict_types=1);

// Local HTTP integration against existing test accounts. No users, orders or demo data are removed.
use App\Components\Auth\AccessTokenPayload;
use App\Components\Auth\JwtTokenService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

if (getenv('APP_ENV') !== 'dev') throw new RuntimeException('Local development only');
require dirname(__DIR__) . '/vendor/autoload.php';
$adminEmail = getenv('TEST_ADMIN_EMAIL');
$adminPassword = getenv('TEST_ADMIN_PASSWORD');
$userEmail = getenv('TEST_USER_EMAIL');
$userPassword = getenv('TEST_USER_PASSWORD');
if (!$adminEmail || !$adminPassword || !$userEmail || !$userPassword) throw new RuntimeException('Provide TEST_ADMIN_EMAIL/PASSWORD and TEST_USER_EMAIL/PASSWORD');
$c = require dirname(__DIR__) . '/config/container.php';
$app = (require dirname(__DIR__) . '/config/app.php')($c);
$checks = 0;
function check(bool $ok, string $message): void { global $checks; if (!$ok) throw new RuntimeException($message); ++$checks; }
$call = static function (string $method, string $path, array $body = [], array $cookies = [], ?string $token = null, int $status = 200) use ($c, $app): array {
    $c->get(EntityManagerInterface::class)->clear();
    $request = (new ServerRequestFactory())->createServerRequest($method, 'http://localhost:8088' . $path)
        ->withParsedBody($body)->withCookieParams($cookies)->withHeader('Accept', 'application/json');
    if ($token) $request = $request->withHeader('Authorization', 'Bearer ' . $token);
    $response = $app->handle($request);
    check($response->getStatusCode() === $status, "$method $path expected $status, got " . $response->getStatusCode());
    $newCookies = [];
    foreach ($response->getHeader('Set-Cookie') as $cookie) {
        [$name, $value] = explode('=', explode(';', $cookie, 2)[0], 2);
        $newCookies[$name] = rawurldecode($value);
    }
    return [json_decode((string)$response->getBody(), true)['data'] ?? [], $newCookies, $response->getHeader('Set-Cookie')];
};
[$login, $adminCookies, $headers] = $call('POST', '/admin/api/auth/login', ['email' => $adminEmail, 'password' => $adminPassword]);
check(array_keys($adminCookies) === ['biofarm_admin_refresh_token'], 'Admin login must only set its own cookie');
check(str_contains($headers[0], 'HttpOnly') && str_contains($headers[0], 'Path=/admin/api/auth') && str_contains($headers[0], 'SameSite=Lax') && !str_contains($headers[0], 'Domain='), 'Admin cookie must be host-only and HttpOnly');
$jwt = $c->get(JwtTokenService::class);
$expired = $jwt->generateAccessToken(new AccessTokenPayload('admin-expired-test', (int)$login['admin']['id'], $login['admin']['first_name'], UserRole::from($login['admin']['role']['id']), time() - 1000, time() - 100));
$call('GET', '/admin/api/auth/me', cookies: $adminCookies, token: $expired, status: 401);
[$site, $siteCookies] = $call('POST', '/v1/auth/login', ['email' => $userEmail, 'password' => $userPassword]);
check(!isset($siteCookies['biofarm_admin_refresh_token']), 'Site login leaves admin cookie intact');
$call('POST', '/admin/api/auth/refresh', cookies: $siteCookies, status: 401);
[$renewed, $rotated] = $call('POST', '/admin/api/auth/refresh', cookies: array_merge($siteCookies, $adminCookies));
check($rotated['biofarm_admin_refresh_token'] !== $adminCookies['biofarm_admin_refresh_token'], 'Refresh rotates token');
check($jwt->decodeAccessToken($renewed['access_token'])->userId === (int)$login['admin']['id'], 'Refresh keeps administrator identity after a different site login');
$call('GET', '/admin/api/auth/me', token: $renewed['access_token']);
$call('GET', '/admin/api/dashboard', token: $renewed['access_token']);
$call('POST', '/admin/api/auth/refresh', cookies: $adminCookies, status: 401);
$call('POST', '/admin/api/auth/refresh', cookies: ['biofarm_admin_refresh_token' => $siteCookies['refresh_token']], status: 403);
[, $discarded] = $call('POST', '/admin/api/auth/logout', cookies: array_merge($siteCookies, $rotated), status: 204);
check($discarded === ['biofarm_admin_refresh_token' => ''], 'Admin logout clears only admin cookie');
$call('POST', '/admin/api/auth/refresh', cookies: $rotated, status: 401);
// The personal account can still rotate its own refresh token after admin logout.
[, $siteRotated] = $call('POST', '/v1/auth/refresh', cookies: $siteCookies);
check(isset($siteRotated['refresh_token']) && !isset($siteRotated['biofarm_admin_refresh_token']), 'Site session is independent');
echo "$checks admin authentication checks passed.\n";
