<?php

declare(strict_types=1);

namespace App\Http\Action\Ozon;

use App\Components\Http\Response\JsonResponse;
use App\Modules\Ozon\OzonConnection;
use App\Modules\Ozon\OzonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class OzonCallbackAction implements RequestHandlerInterface
{
    public function __construct(private OzonConnection $connection) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $state = $params['state'] ?? null;
        $code = $params['code'] ?? null;
        try {
            $this->connection->complete(is_string($state) ? $state : '', is_string($code) ? $code : '');
        } catch (OzonException $exception) {
            return (new JsonResponse(['error' => $exception->getMessage()], 400))
                ->withHeader('Cache-Control', 'no-store')
                ->withHeader('Referrer-Policy', 'no-referrer');
        }

        return (new Response(303))
            ->withHeader('Location', '/admin/settings/integrations')
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('Referrer-Policy', 'no-referrer');
    }
}
