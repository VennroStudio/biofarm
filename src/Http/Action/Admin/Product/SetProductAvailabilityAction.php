<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Product;

use App\Components\Cacher\Cacher;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Http\Response\JsonResponse;
use App\Components\Router\Route;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class SetProductAvailabilityAction implements RequestHandlerInterface
{
    public function __construct(private Connection $db, private Cacher $cache) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = Route::getArgumentToInt($request, 'id');
        $body = $request->getParsedBody();
        $availability = is_array($body) ? ($body['availability'] ?? null) : null;
        if (!in_array($availability, ['in_stock', 'out_of_stock'], true)) {
            return new JsonResponse(['error' => 'Выберите наличие товара.'], 422);
        }

        $result = $this->db->transactional(function () use ($id, $availability): int {
            $row = $this->db->fetchAssociative('SELECT price FROM products WHERE id = ? AND deleted_at IS NULL FOR UPDATE', [$id]);
            if ($row === false) {
                return 404;
            }

            if ($availability === 'in_stock') {
                $stock = $this->db->fetchOne('SELECT stock + fbs_stock FROM ozon_catalog_products WHERE product_id = ?', [$id]);
                if ($stock !== false && ((int) $stock === 0 || (int) $row['price'] <= 0)) {
                    return 409;
                }
            }

            $this->db->update('products', [
                'availability' => $availability,
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ], ['id' => $id]);
            return 200;
        });

        if ($result === 404) {
            return new JsonResponse(['error' => 'Товар не найден.'], 404);
        }
        if ($result === 409) {
            return new JsonResponse(['error' => 'Товар станет доступен после подтверждения остатка и цены в Ozon.'], 409);
        }

        $this->cache->deleteTag('products');

        return new JsonDataResponse(['availability' => $availability]);
    }
}
