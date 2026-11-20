<?php

declare(strict_types=1);

namespace App\Http\Web\Profile;

use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class PartnerPageController implements RequestHandlerInterface
{
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(301)->withHeader('Location', '/profile?tab=partner-orders');
    }
}
