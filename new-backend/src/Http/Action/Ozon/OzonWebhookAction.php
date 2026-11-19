<?php

declare(strict_types=1);

namespace App\Http\Action\Ozon;

use App\Components\Http\Response\JsonResponse;
use App\Components\Router\Route;
use App\Modules\Ozon\OzonWebhook;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class OzonWebhookAction implements RequestHandlerInterface
{
    public function __construct(private OzonWebhook $webhook) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->webhook->accepts(Route::getArgument($request, 'secret'))) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $event = $request->getParsedBody();
        if (!is_array($event) || !is_string($event['message_type'] ?? null)) {
            return new JsonResponse(['error' => 'Invalid notification'], 400);
        }
        if ($event['message_type'] === 'TYPE_PING') {
            return new JsonResponse(['version' => '1.0', 'name' => 'Biofarm', 'time' => gmdate('c')]);
        }
        if ($event['message_type'] === 'TYPE_FBO_STOCKS_CHANGED') {
            $this->webhook->record($event);
        }
        return new JsonResponse(['result' => true]);
    }
}
