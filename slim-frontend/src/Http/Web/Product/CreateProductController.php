<?php

declare(strict_types=1);

namespace App\Http\Web\Product;

use App\Components\Api\ApiException;
use App\Components\Http\Response\HtmlResponse;
use App\Modules\Product\Command\CreateProduct\CreateProductCommand;
use App\Modules\Product\Command\CreateProduct\CreateProductHandler;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

final readonly class CreateProductController implements RequestHandlerInterface
{
    public function __construct(
        private CreateProductHandler $handler,
        private Environment $twig,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = ProductFormData::fromRequest($request);
        $result = null;
        $error = null;

        try {
            $result = $this->handler->handle(new CreateProductCommand(
                title: ProductFormData::string($data, 'title'),
                price: ProductFormData::float($data, 'price'),
                description: ProductFormData::string($data, 'description'),
                category: ProductFormData::string($data, 'category'),
                brand: ProductFormData::string($data, 'brand'),
                stock: ProductFormData::int($data, 'stock'),
                image: ProductFormData::string($data, 'image'),
            ));
        } catch (ApiException $exception) {
            $error = $exception->getMessage();
        }

        return new HtmlResponse($this->twig->render('pages/product-command/result.html.twig', [
            'action' => [
                'title'    => 'Create product',
                'method'   => 'POST',
                'endpoint' => '/products/create',
            ],
            'product' => $result,
            'delete'  => null,
            'error'   => $error,
        ]), $error === null ? 200 : 502);
    }
}
