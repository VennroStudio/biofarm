<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Payment;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Router\Route;
use App\Modules\Ozon\OzonPickupCheckout;
use App\Modules\Payment\Service\PaymentService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class PaymentAction implements RequestHandlerInterface
{
    public function __construct(private PaymentService $payments, private OzonPickupCheckout $ozonCheckout) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = (string)Route::getArgument($request, 'orderId');
        $identity = RequestIdentity::find($request);
        $payload = (array)$request->getParsedBody();
        $token = $request->getHeaderLine('X-Order-Token') ?: (string)($payload['token'] ?? '');
        $order = $this->payments->authorize($id, $identity?->id, $token, $identity?->role === UserRole::ADMIN);
        $result = $request->getMethod() === 'POST' ? $this->payments->start($id) : $this->payments->status($id);
        if ($order['delivery_method'] === OzonPickupCheckout::LIVE_PICKUP) {
            $delivery = $this->ozonCheckout->status($id);
            if ($delivery !== null) {
                $result['ozonDelivery'] = $delivery;
            }
        }
        return new JsonDataResponse($result);
    }
}
