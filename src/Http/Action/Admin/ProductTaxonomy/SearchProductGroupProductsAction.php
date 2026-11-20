<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\ProductTaxonomy;

use App\Components\Http\Response\JsonDataItemsResponse;
use App\Modules\Product\Service\ProductGroupMembers;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class SearchProductGroupProductsAction implements RequestHandlerInterface
{
    public function __construct(private ProductGroupMembers $members) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $search = $request->getQueryParams()['search'] ?? '';
        $items = $this->members->search(is_string($search) ? trim($search) : '');
        return new JsonDataItemsResponse(count($items), $items);
    }
}
