<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Order;

use App\Modules\Query\Orders\GetByReferrerId\Fetcher;
use App\Modules\Query\Orders\GetByReferrerId\Query;
use App\Modules\Entity\OrderItem\OrderItemRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class GetByReferrerIdAction implements RequestHandlerInterface
{
    public function __construct(
        private Fetcher $fetcher,
        private OrderItemRepository $orderItemRepository,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Try multiple ways to get the referrerId from route
        $route = $request->getAttribute('route');
        $referrerId = 0;
        
        if ($route) {
            $referrerId = (int)($route->getArgument('referrerId') ?? 0);
        }
        
        // If still 0, try to get from URI (fallback for Slim routing issues)
        if ($referrerId === 0) {
            $path = $request->getUri()->getPath();
            if (preg_match('/\/orders\/referrer\/(\d+)/', $path, $matches)) {
                $referrerId = (int)$matches[1];
            }
        }

        if ($referrerId === 0) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Referrer ID not found'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $query = new Query(referrerId: $referrerId);
        $orders = $this->fetcher->fetch($query);

        $response = new Response();
        $serialized = array_map(function ($order) {
            $items = $this->orderItemRepository->findByOrderId($order->getId());
            $itemsData = array_map(function ($item) {
                return [
                    'productId' => $item->getProductId(),
                    'productName' => $item->getProductName(),
                    'price' => $item->getPrice(),
                    'quantity' => $item->getQuantity(),
                ];
            }, $items);
            return \App\Http\Serializer::order($order, $itemsData);
        }, $orders);
        $response->getBody()->write(json_encode($serialized, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
