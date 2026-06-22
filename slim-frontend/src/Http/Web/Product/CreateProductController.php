<?php

declare(strict_types=1);

namespace App\Http\Web\Product;

use App\Components\Api\ApiException;
use App\Components\Http\Form\FormValidationException;
use App\Components\Security\CsrfToken;
use App\Modules\Product\Command\CreateProduct\CreateProductCommand;
use App\Modules\Product\Command\CreateProduct\CreateProductHandler;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class CreateProductController implements RequestHandlerInterface
{
    public function __construct(
        private CreateProductHandler $handler,
        private ProductCommandResponder $responder,
        private CsrfToken $csrf,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = ProductFormData::fromRequest($request);
        $result = null;
        $error = null;
        $status = 200;

        try {
            $this->csrf->validate('products.create', ProductFormData::string($data, '_csrf_token'));
            $result = $this->handler->handle(new CreateProductCommand(
                title: ProductFormData::requiredString($data, 'title'),
                price: ProductFormData::requiredFloat($data, 'price', 0.0),
                description: ProductFormData::requiredString($data, 'description'),
                category: ProductFormData::requiredString($data, 'category'),
                brand: ProductFormData::requiredString($data, 'brand'),
                stock: ProductFormData::requiredInt($data, 'stock', 0),
                image: ProductFormData::requiredString($data, 'image'),
            ));
        } catch (FormValidationException $exception) {
            $error = $exception->getMessage();
            $status = $exception->statusCode();
        } catch (ApiException $exception) {
            $error = $exception->getMessage();
            $status = 502;
        }

        return $this->responder->respond(
            request: $request,
            action: [
                'title'       => 'Create product',
                'description' => 'Result of the product create command handler.',
                'method'      => 'POST',
                'endpoint'    => '/products/create',
            ],
            product: $result,
            delete: null,
            error: $error,
            status: $status,
        );
    }
}
