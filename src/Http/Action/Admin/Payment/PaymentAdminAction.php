<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Payment;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Router\Route;
use App\Modules\Ozon\OzonFulfillment;
use App\Modules\Ozon\OzonPickupCheckout;
use App\Modules\Payment\Service\PaymentService;
use App\Modules\Payment\Service\YooKassaGateway;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class PaymentAdminAction implements RequestHandlerInterface
{
    public function __construct(
        private PaymentService $payments,
        private YooKassaGateway $gateway,
        private Connection $db,
        private OzonFulfillment $ozon,
        private OzonPickupCheckout $checkout,
    ) {}

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
            $ozon = $this->checkout->status($orderId);
            if ($ozon !== null) {
                $ozon['error'] = $this->db->fetchOne('SELECT error FROM ozon_delivery_orders WHERE order_id=?', [$orderId]);
            }
            $fiscal = $this->db->fetchOne("SELECT fiscal_items FROM ozon_delivery_orders WHERE order_id=? AND mode='live'", [$orderId]);
            $fiscal = is_string($fiscal) ? json_decode($fiscal, true, flags: JSON_THROW_ON_ERROR) : [];
            $items = $this->db->fetchAllAssociative('SELECT id,product_name,price,quantity FROM order_items WHERE order_id=?', [$orderId]);
            foreach ($items as &$item) {
                $item['has_marking'] = !empty($fiscal[$item['id']]['marks']);
            }
            unset($item);
            $postings = $this->db->fetchAllKeyValue('SELECT receipt_operation_id,posting_number FROM ozon_delivered_postings WHERE order_id=?', [$orderId]);
            $operations = $this->db->fetchAllAssociative('SELECT id,kind,status,amount_minor,created_at FROM payment_operations WHERE order_id=? ORDER BY created_at DESC,id', [$orderId]);
            foreach ($operations as &$operation) {
                $operation['posting_number'] = $postings[$operation['id']] ?? null;
            }
            unset($operation);
            return new JsonDataResponse(['payment' => $this->payments->status($orderId), 'ozonDelivery' => $ozon,
                'items' => $items, 'operations' => $operations]);
        }
        if (str_ends_with($path, '/receipt')) {
            if (isset($payload['providerReceiptId'])) {
                return new JsonDataResponse($this->payments->recoverSettlementReceipt($orderId, (string)$payload['providerReceiptId'],
                    (string)($payload['reason'] ?? ''), RequestIdentity::get($request)->id,
                    isset($payload['receiptOperationId']) ? (string)$payload['receiptOperationId'] : null));
            }
            return new JsonDataResponse($this->payments->settlementReceipt($orderId));
        }
        if (str_ends_with($path, '/ozon-resolution')) {
            return new JsonDataResponse($this->ozon->resolveSubmission($orderId,
                trim((string)($payload['externalNumber'] ?? '')), ($payload['confirmedMissing'] ?? false) === true,
                (string)($payload['reason'] ?? ''), RequestIdentity::get($request)->id));
        }
        $items = (array)($payload['items'] ?? []);
        $delivery = ($payload['refundDelivery'] ?? false) === true;
        $cancel = $this->ozon->requestCancellation($orderId, $items, $delivery);
        return new JsonDataResponse($cancel ?? $this->payments->requestRefund($orderId, (string)($payload['requestId'] ?? ''), $items, $delivery));
    }
}
