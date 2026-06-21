<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Blog;

use App\Components\Http\Response\JsonDataResponse;
use App\Components\Router\Route;
use App\Http\Unifier\Blog\BlogPostUnifier;
use App\Modules\Blog\Query\BlogPost\GetById\BlogPostGetByIdFetcher;
use App\Modules\Blog\Query\BlogPost\GetById\BlogPostGetByIdQuery;
use Doctrine\DBAL\Exception;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[OA\Get(path: '/blog/{id}', summary: 'Статья блога по ID', tags: ['Blog'])]
final readonly class GetBlogPostByIdAction implements RequestHandlerInterface
{
    public function __construct(
        private BlogPostGetByIdFetcher $fetcher,
        private BlogPostUnifier $unifier,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $post = $this->fetcher->fetch(new BlogPostGetByIdQuery(
            id: Route::getArgumentToInt($request, 'id'),
            onlyPublished: !isset($params['all']) && (($params['onlyPublished'] ?? true) !== 'false'),
        ));

        return new JsonDataResponse($this->unifier->unifyOne(null, $post));
    }
}
