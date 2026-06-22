<?php

declare(strict_types=1);

namespace App\Http\Web\Product;

use App\Components\Api\ApiException;
use App\Components\Http\Form\FormValidationException;
use App\Components\Security\CsrfToken;
use App\Components\Twig\HtmlResponder;
use App\Modules\Product\Command\UpdateProduct\UpdateProductCommand;
use App\Modules\Product\Command\UpdateProduct\UpdateProductHandler;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class UpdateProductController implements RequestHandlerInterface
{
    public function __construct(
        private UpdateProductHandler $handler,
        private HtmlResponder $html,
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
            $this->csrf->validate('products.update', ProductFormData::string($data, '_csrf_token'));
            $result = $this->handler->handle(new UpdateProductCommand(
                id: ProductFormData::requiredInt($data, 'id', 1),
                title: ProductFormData::stringOrNull($data, 'title'),
                price: ProductFormData::optionalFloat($data, 'price', 0.0),
                description: ProductFormData::stringOrNull($data, 'description'),
                category: ProductFormData::stringOrNull($data, 'category'),
                brand: ProductFormData::stringOrNull($data, 'brand'),
                stock: ProductFormData::optionalInt($data, 'stock', 0),
                image: ProductFormData::stringOrNull($data, 'image'),
            ));
        } catch (FormValidationException $exception) {
            $error = $exception->getMessage();
            $status = $exception->statusCode();
        } catch (ApiException $exception) {
            $error = $exception->getMessage();
            $status = 502;
        }

        return $this->html->render('pages/product-command/result.html.twig', [
            'action' => [
                'title'    => 'Update product',
                'method'   => 'PATCH',
                'endpoint' => '/products/update',
            ],
            'product' => $result,
            'delete'  => null,
            'error'   => $error,
        ], $status);
    }
}
