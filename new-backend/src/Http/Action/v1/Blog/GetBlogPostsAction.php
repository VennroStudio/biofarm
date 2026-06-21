<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Blog;

use App\Components\Http\Response\JsonDataItemsResponse;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Http\Unifier\Blog\BlogPostUnifier;
use App\Modules\Blog\Query\BlogPost\FindAll\BlogPostFindAllFetcher;
use App\Modules\Blog\Query\BlogPost\FindAll\BlogPostFindAllQuery;
use Doctrine\DBAL\Exception;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[OA\Get(path: '/blog', summary: 'Список статей блога', tags: ['Blog'])]
final readonly class GetBlogPostsAction implements RequestHandlerInterface
{
    public function __construct(
        private BlogPostFindAllFetcher $fetcher,
        private BlogPostUnifier $unifier,
        private Denormalizer $denormalizer,
        private Validator $validator,
    ) {}

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $query = $this->denormalizer->denormalize($params, BlogPostFindAllQuery::class);

        if (isset($params['all']) || (($params['onlyPublished'] ?? null) === 'false')) {
            $query->onlyPublished = false;
        }

        $this->validator->validate($query);
        $result = $this->fetcher->fetch($query);

        return new JsonDataItemsResponse(
            count: $result->count,
            items: $this->unifier->unify(null, $result->items),
        );
    }
}
