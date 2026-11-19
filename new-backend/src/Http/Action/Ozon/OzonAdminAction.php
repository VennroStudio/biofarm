<?php

declare(strict_types=1);

namespace App\Http\Action\Ozon;

use App\Components\Exception\AccessDeniedException;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Http\Response\JsonResponse;
use App\Modules\Ozon\OzonCatalogSync;
use App\Modules\Ozon\OzonConnection;
use App\Modules\Ozon\OzonException;
use App\Modules\Ozon\OzonWebhook;
use App\Modules\Ozon\OzonPickupCheckout;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class OzonAdminAction implements RequestHandlerInterface
{
    public function __construct(
        private OzonConnection $connection,
        private OzonCatalogSync $catalog,
        private OzonWebhook $webhook,
        private OzonPickupCheckout $checkout,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!in_array(RequestIdentity::get($request)->role, [UserRole::ADMIN, UserRole::DEVELOPER], true)) {
            throw new AccessDeniedException();
        }

        $path = $request->getUri()->getPath();
        try {
            if ($request->getMethod() === 'POST' && str_ends_with($path, '/connect')) {
                return (new JsonDataResponse(['url' => $this->connection->authorizeUrl()]))->withHeader('Cache-Control', 'no-store');
            }
            if ($request->getMethod() === 'POST') {
                $this->catalog->request();
                return new JsonDataResponse($this->status(), 202);
            }
            return (new JsonDataResponse($this->status()))->withHeader('Cache-Control', 'no-store');
        } catch (OzonException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 422);
        }
    }

    private function status(): array
    {
        return $this->catalog->status() + [
            'configured' => $this->connection->configured(),
            'connected' => $this->connection->connected(),
            'redirectUri' => $this->connection->redirectUri,
            'webhookUrl' => $this->webhook->url(),
            'deliveryEnabled' => $this->checkout->method() === OzonPickupCheckout::LIVE_PICKUP,
            'deliveryReady' => $this->checkout->ready(),
        ];
    }
}
