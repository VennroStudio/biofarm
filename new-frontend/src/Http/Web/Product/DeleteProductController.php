<?php

declare(strict_types=1);

namespace App\Http\Web\Product;

use App\Components\Api\ApiException;
use App\Components\Http\Form\FormData;
use App\Components\Http\Form\FormValidationException;
use App\Components\Security\CsrfToken;
use App\Modules\Product\Command\DeleteProduct\DeleteProductCommand;
use App\Modules\Product\Command\DeleteProduct\DeleteProductHandler;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class DeleteProductController implements RequestHandlerInterface
{
    public function __construct(
        private DeleteProductHandler $handler,
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
            $this->csrf->validate('products.delete', FormData::string($data, '_csrf_token'));
            $result = $this->handler->handle(new DeleteProductCommand(
                id: FormData::requiredInt($data, 'id', 1),
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
                'title'       => 'Delete product',
                'description' => 'Result of the product delete command handler.',
                'method'      => 'DELETE',
                'endpoint'    => '/products/delete',
            ],
            product: null,
            delete: $result,
            error: $error,
            status: $status,
        );
    }
}
