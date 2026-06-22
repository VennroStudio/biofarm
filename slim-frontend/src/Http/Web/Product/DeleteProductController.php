<?php

declare(strict_types=1);

namespace App\Http\Web\Product;

use App\Components\Api\ApiException;
use App\Components\Http\Response\HtmlResponse;
use App\Modules\Product\Command\DeleteProduct\DeleteProductCommand;
use App\Modules\Product\Command\DeleteProduct\DeleteProductHandler;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

final readonly class DeleteProductController implements RequestHandlerInterface
{
    public function __construct(
        private DeleteProductHandler $handler,
        private Environment $twig,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = ProductFormData::fromRequest($request);
        $result = null;
        $error = null;

        try {
            $result = $this->handler->handle(new DeleteProductCommand(
                id: ProductFormData::int($data, 'id'),
            ));
        } catch (ApiException $exception) {
            $error = $exception->getMessage();
        }

        return new HtmlResponse($this->twig->render('pages/product-command/result.html.twig', [
            'action' => [
                'title'    => 'Delete product',
                'method'   => 'DELETE',
                'endpoint' => '/products/delete',
            ],
            'product' => null,
            'delete'  => $result,
            'error'   => $error,
        ]), $error === null ? 200 : 502);
    }
}
