<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Page;

use App\Components\Http\Response\JsonDataItemsResponse;
use App\Modules\Page\Query\Page\FindAll\PageFindAllFetcher;
use App\Modules\Page\Query\Page\FindAll\PageFindAllQuery;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetPagesAction implements RequestHandlerInterface
{
    public function __construct(
        private PageFindAllFetcher $pages,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        unset($request);
        $result = $this->pages->fetch(new PageFindAllQuery());

        return new JsonDataItemsResponse(
            $result->count,
            array_map(static fn ($page): array => $page->toArray(), $result->items),
        );
    }
}
