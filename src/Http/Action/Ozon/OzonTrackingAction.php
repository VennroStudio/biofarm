<?php

declare(strict_types=1);

namespace App\Http\Action\Ozon;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Http\Response\JsonResponse;
use App\Components\Router\Route;
use App\Modules\Ozon\OzonPickupCheckout;
use App\Modules\Payment\Service\PaymentService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class OzonTrackingAction implements RequestHandlerInterface
{
    public function __construct(private OzonPickupCheckout $checkout, private PaymentService $payments) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $orderId = (string) Route::getArgument($request, 'orderId');
        $identity = RequestIdentity::find($request);
        $order = $this->payments->authorize($orderId, $identity?->id, $request->getHeaderLine('X-Order-Token'), $identity?->role === UserRole::ADMIN);

        $postings = $order['delivery_method'] === OzonPickupCheckout::LIVE_PICKUP ? $this->checkout->tracking($orderId) : null;
        return $postings === null ? new JsonResponse(['error' => 'Отправление пока недоступно.'], 404)
            : new JsonDataResponse($postings);
    }
}
