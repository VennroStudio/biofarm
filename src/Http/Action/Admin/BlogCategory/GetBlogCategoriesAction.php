<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\BlogCategory;

use App\Components\Http\Response\JsonDataItemsResponse;
use App\Modules\Blog\Service\BlogCategoryService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetBlogCategoriesAction implements RequestHandlerInterface
{
    public function __construct(private BlogCategoryService $categories) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $items = $this->categories->all();
        return new JsonDataItemsResponse(\count($items), $items);
    }
}
