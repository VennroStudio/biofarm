<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Order;

use App\Modules\Query\Orders\GetByUserId\Fetcher;
use App\Modules\Query\Orders\GetByUserId\Query;
use App\Modules\Entity\OrderItem\OrderItemRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class GetByUserIdAction implements RequestHandlerInterface
{
    public function __construct(
        private Fetcher $fetcher,
        private OrderItemRepository $orderItemRepository,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Try multiple ways to get the userId from route
        $route = $request->getAttribute('route');
        $userId = 0;
        
        if ($route) {
            $userId = (int)($route->getArgument('userId') ?? 0);
        }
        
        // If still 0, try to get from URI (fallback for Slim routing issues)
        if ($userId === 0) {
            $path = $request->getUri()->getPath();
            if (preg_match('/\/orders\/user\/(\d+)/', $path, $matches)) {
                $userId = (int)$matches[1];
            }
        }

        $query = new Query(userId: $userId);
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
