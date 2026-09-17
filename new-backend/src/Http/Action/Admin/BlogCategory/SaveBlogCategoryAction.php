<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\BlogCategory;

use App\Components\Http\Response\JsonDataResponse;
use App\Modules\Blog\Service\BlogCategoryService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

final readonly class SaveBlogCategoryAction implements RequestHandlerInterface
{
    public function __construct(private BlogCategoryService $categories) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = RouteContext::fromRequest($request)->getRoute()?->getArgument('id');
        $savedId = $this->categories->save($id === null ? null : (int)$id, (array)$request->getParsedBody());
        return new JsonDataResponse(['id' => $savedId], $id === null ? 201 : 200);
    }
}
