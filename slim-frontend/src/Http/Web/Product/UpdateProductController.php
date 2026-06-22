<?php

declare(strict_types=1);

namespace App\Http\Web\Product;

use App\Components\Api\ApiException;
use App\Components\Http\Form\FormData;
use App\Components\Http\Form\FormValidationException;
use App\Components\Security\CsrfToken;
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
        private ProductCommandResponder $responder,
        private CsrfToken $csrf,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = FormData::fromRequest($request);
        $result = null;
        $error = null;
        $status = 200;

        try {
            $this->csrf->validate('products.update', FormData::string($data, '_csrf_token'));
            $result = $this->handler->handle(new UpdateProductCommand(
                id: FormData::requiredInt($data, 'id', 1),
                title: FormData::stringOrNull($data, 'title'),
                price: FormData::optionalFloat($data, 'price', 0.0),
                description: FormData::stringOrNull($data, 'description'),
                category: FormData::stringOrNull($data, 'category'),
                brand: FormData::stringOrNull($data, 'brand'),
                stock: FormData::optionalInt($data, 'stock', 0),
                image: FormData::stringOrNull($data, 'image'),
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
                'title'       => 'Update product',
                'description' => 'Result of the product update command handler.',
                'method'      => 'PATCH',
                'endpoint'    => '/products/update',
            ],
            product: $result,
            delete: null,
            error: $error,
            status: $status,
        );
    }
}
