<?php

declare(strict_types=1);

namespace App\Http\Web\Product;

use App\Components\Http\Response\JsonResponseFactory;
use App\Modules\Product\Api\Response\ProductDeleteResponse;
use App\Modules\Product\Api\Response\ProductResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ProductCommandResponder
{
    public function __construct(
        private JsonResponseFactory $json,
        private ResponseFactoryInterface $responseFactory,
    ) {}

    /**
     * @param array{title: string, description: string, method: string, endpoint: string} $action
     */
    public function respond(
        ServerRequestInterface $request,
        array $action,
        ?ProductResponse $product,
        ?ProductDeleteResponse $delete,
        ?string $error,
        int $status,
    ): ResponseInterface {
        if ($this->wantsJson($request)) {
            return $this->json->create($this->payload($action, $product, $delete, $error), $status);
        }

        return $this->responseFactory->createResponse(303)
            ->withHeader('Location', '/#product-commands');
    }

    private function wantsJson(ServerRequestInterface $request): bool
    {
        return str_contains($request->getHeaderLine('Accept'), 'application/json')
            || $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * @param array{title: string, description: string, method: string, endpoint: string} $action
     * @return array{
     *     ok: bool,
     *     message: string,
     *     action: array{title: string, description: string, method: string, endpoint: string},
     *     product: array{id: int, title: string, price: float, description: string, category: string, brand: string, stock: int, image: string, ratingRate: float, ratingCount: int}|null,
     *     delete: array{id: int, deleted: bool, message: string}|null
     * }
     */
    private function payload(
        array $action,
        ?ProductResponse $product,
        ?ProductDeleteResponse $delete,
        ?string $error,
    ): array {
        return [
            'ok'      => $error === null,
            'message' => $error ?? $this->successMessage($action, $product, $delete),
            'action'  => $action,
            'product' => $product === null ? null : [
                'id'          => $product->id,
                'title'       => $product->title,
                'price'       => $product->price,
                'description' => $product->description,
                'category'    => $product->category,
                'brand'       => $product->brand,
                'stock'       => $product->stock,
                'image'       => $product->image,
                'ratingRate'  => $product->ratingRate,
                'ratingCount' => $product->ratingCount,
            ],
            'delete' => $delete === null ? null : [
                'id'      => $delete->id,
                'deleted' => $delete->deleted,
                'message' => $delete->message,
            ],
        ];
    }

    /**
     * @param array{title: string, description: string, method: string, endpoint: string} $action
     */
    private function successMessage(
        array $action,
        ?ProductResponse $product,
        ?ProductDeleteResponse $delete,
    ): string {
        if ($delete !== null) {
            return $delete->message;
        }

        if ($product !== null) {
            return $action['title'] . ': ' . $product->title;
        }

        return $action['title'] . ' completed.';
    }
}
