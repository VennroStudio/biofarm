<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Payment;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Router\Route;
use App\Modules\Payment\Service\PaymentService;
use App\Modules\Payment\Service\YooKassaGateway;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class PaymentAdminAction implements RequestHandlerInterface
{
    public function __construct(private PaymentService $payments, private YooKassaGateway $gateway, private Connection $db) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        if (str_ends_with($path, '/config')) {
            return new JsonDataResponse($this->gateway->publicConfig());
        }
        if (str_ends_with($path, '/reconcile')) {
            return new JsonDataResponse($this->payments->reconcile());
        }
        $payload = (array)$request->getParsedBody();
        $orderId = (string)Route::getArgument($request, 'orderId');
        if ($request->getMethod() === 'GET') {
            return new JsonDataResponse(['payment' => $this->payments->status($orderId), 'items' => $this->db->fetchAllAssociative('SELECT id,product_name,price,quantity FROM order_items WHERE order_id=?', [$orderId]), 'operations' => $this->db->fetchAllAssociative('SELECT id,kind,status,amount_minor,created_at FROM payment_operations WHERE order_id=? ORDER BY created_at DESC', [$orderId])]);
        }
        if (str_ends_with($path, '/receipt')) {
            if (isset($payload['providerReceiptId'])) {
                return new JsonDataResponse($this->payments->recoverSettlementReceipt($orderId, (string)$payload['providerReceiptId'], (string)($payload['reason'] ?? ''), RequestIdentity::get($request)->id));
            }
            return new JsonDataResponse($this->payments->settlementReceipt($orderId));
        }
        return new JsonDataResponse($this->payments->requestRefund($orderId, (string)($payload['requestId'] ?? ''), (array)($payload['items'] ?? []), (bool)($payload['refundDelivery'] ?? false)));
    }
}
