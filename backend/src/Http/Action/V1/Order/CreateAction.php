<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Order;

use App\Modules\Command\Order\Create\Command;
use App\Modules\Command\Order\Create\Handler;
use App\Modules\Command\OrderItem\Create\Command as OrderItemCreateCommand;
use App\Modules\Command\OrderItem\Create\Handler as OrderItemCreateHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class CreateAction implements RequestHandlerInterface
{
    public function __construct(
        private Handler $handler,
        private OrderItemCreateHandler $orderItemHandler,
        private EntityManagerInterface $em,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string)$request->getBody(), true);

        $orderId = 'ORD-' . strtoupper(substr(uniqid(), -8));
        $command = new Command(
            orderId: $orderId,
            userId: (int)($body['userId'] ?? 0),
            total: (int)($body['total'] ?? 0),
            shippingAddress: $body['shippingAddress'] ?? [],
            paymentMethod: $body['paymentMethod'] ?? 'card',
            bonusUsed: (int)($body['bonusUsed'] ?? 0),
            referredBy: $body['referredBy'] ?? null,
        );

        try {
            $order = $this->handler->handle($command);
            $this->em->flush();

            // Create order items
            foreach ($body['items'] ?? [] as $item) {
                $this->orderItemHandler->handle(new OrderItemCreateCommand(
                    orderId: $order->getId(),
                    productId: (int)$item['productId'],
                    productName: $item['productName'] ?? '',
                    price: (int)$item['price'],
                    quantity: (int)$item['quantity'],
                ));
            }
            $this->em->flush();

            $response = new Response();
            $response->getBody()->write(json_encode([
                'id' => $order->getId(),
                'userId' => $order->getUserId(),
                'status' => $order->getStatus(),
                'paymentStatus' => $order->getPaymentStatus(),
                'total' => $order->getTotal(),
            ], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}
