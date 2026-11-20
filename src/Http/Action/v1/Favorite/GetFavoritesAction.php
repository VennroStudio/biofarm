<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Favorite;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataItemsResponse;
use App\Components\Setting\SiteSettings;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetFavoritesAction implements RequestHandlerInterface
{
    public function __construct(
        private Connection $connection,
        private SiteSettings $settings,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->settings->bool('favorites_enabled')) {
            throw new DomainExceptionModule('user', 'error.favorites_disabled', 35, status: 403);
        }

        $identity = RequestIdentity::get($request);
        $rows = $this->connection->createQueryBuilder()
            ->select(
                'p.id',
                'p.slug',
                'p.name',
                'p.price',
                'p.old_price',
                'p.image',
                'p.image_alt',
                'p.weight',
                'p.short_description',
                'f.created_at AS favorited_at',
            )
            ->from('favorites', 'f')
            ->innerJoin('f', 'products', 'p', 'p.id = f.product_id AND p.deleted_at IS NULL AND p.is_active = 1')
            ->where('f.user_id = :userId')
            ->setParameter('userId', $identity->id)
            ->orderBy('f.created_at', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        $items = array_map(static fn (array $row): array => [
            'id'                => (int)$row['id'],
            'slug'              => (string)$row['slug'],
            'name'              => (string)$row['name'],
            'title'             => (string)$row['name'],
            'price'             => (int)$row['price'],
            'old_price'         => $row['old_price'] !== null ? (int)$row['old_price'] : null,
            'image'             => (string)$row['image'],
            'image_alt'         => $row['image_alt'],
            'weight'            => (string)$row['weight'],
            'short_description' => $row['short_description'],
            'favorited_at'      => (string)$row['favorited_at'],
        ], $rows);

        return new JsonDataItemsResponse(count: \count($items), items: $items);
    }
}
