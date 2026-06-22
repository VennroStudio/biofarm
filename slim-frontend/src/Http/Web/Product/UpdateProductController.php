<?php

declare(strict_types=1);

namespace App\Http\Web\Product;

use App\Components\Api\ApiException;
use App\Components\Http\Response\HtmlResponse;
use App\Modules\Product\Command\UpdateProduct\UpdateProductCommand;
use App\Modules\Product\Command\UpdateProduct\UpdateProductHandler;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

final readonly class UpdateProductController implements RequestHandlerInterface
{
    public function __construct(
        private UpdateProductHandler $handler,
        private Environment $twig,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = ProductFormData::fromRequest($request);
        $result = null;
        $error = null;

        try {
            $result = $this->handler->handle(new UpdateProductCommand(
                id: ProductFormData::int($data, 'id'),
                title: ProductFormData::stringOrNull($data, 'title'),
                price: ProductFormData::floatOrNull($data, 'price'),
                description: ProductFormData::stringOrNull($data, 'description'),
                category: ProductFormData::stringOrNull($data, 'category'),
                brand: ProductFormData::stringOrNull($data, 'brand'),
                stock: ProductFormData::intOrNull($data, 'stock'),
                image: ProductFormData::stringOrNull($data, 'image'),
            ));
        } catch (ApiException $exception) {
            $error = $exception->getMessage();
        }

        return new HtmlResponse($this->twig->render('pages/product-command/result.html.twig', [
            'action' => [
                'title'    => 'Update product',
                'method'   => 'PATCH',
                'endpoint' => '/products/update',
            ],
            'product' => $result,
            'delete'  => null,
            'error'   => $error,
        ]), $error === null ? 200 : 502);
    }
}
