<?php

declare(strict_types=1);

namespace App\Http\Web\Product;

use App\Components\Http\Form\FormData;
use App\Components\Http\Form\FormValidationException;
use App\Components\Security\CsrfToken;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class DeleteProductController implements RequestHandlerInterface
{
    public function __construct(
        private CsrfToken $csrf,
        private ResponseFactoryInterface $responseFactory,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = FormData::fromRequest($request);

        try {
            $this->csrf->validate('products.delete', FormData::string($data, '_csrf_token'));
            FormData::requiredInt($data, 'id', 1);
        } catch (FormValidationException) {
            return $this->redirectToProducts();
        }

        return $this->redirectToProducts();
    }

    private function redirectToProducts(): ResponseInterface
    {
        return $this->responseFactory->createResponse(303)
            ->withHeader('Location', '/#catalog');
    }
}
