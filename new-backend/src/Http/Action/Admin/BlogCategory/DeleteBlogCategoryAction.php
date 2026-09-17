<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\BlogCategory;

use App\Components\Http\Response\JsonDataResponse;
use App\Modules\Blog\Service\BlogCategoryService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

final readonly class DeleteBlogCategoryAction implements RequestHandlerInterface
{
    public function __construct(private BlogCategoryService $categories) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int)RouteContext::fromRequest($request)->getRoute()?->getArgument('id');
        $this->categories->delete($id);
        return new JsonDataResponse(['deleted' => true]);
    }
}
