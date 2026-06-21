<?php

declare(strict_types=1);

use App\Modules\User\Entity\User\Persistence\Doctrine\DoctrineUserRepository;
use App\Modules\User\Entity\User\UserRepository;
use App\Modules\User\Entity\UserToken\Persistence\Doctrine\DoctrineUserTokenRepository;
use App\Modules\User\Entity\UserToken\UserTokenRepository;
use App\Modules\Product\Entity\Product\Persistence\Doctrine\DoctrineProductRepository;
use App\Modules\Product\Entity\Product\ProductRepository;
use App\Modules\Product\Entity\ProductCategory\Persistence\Doctrine\DoctrineProductCategoryRepository;
use App\Modules\Product\Entity\ProductCategory\ProductCategoryRepository;
use App\Modules\Blog\Entity\BlogPost\BlogPostRepository;
use App\Modules\Blog\Entity\BlogPost\Persistence\Doctrine\DoctrineBlogPostRepository;
use App\Modules\Order\Entity\Order\OrderRepository;
use App\Modules\Order\Entity\Order\Persistence\Doctrine\DoctrineOrderRepository;
use App\Modules\Order\Entity\OrderItem\OrderItemRepository;
use App\Modules\Order\Entity\OrderItem\Persistence\Doctrine\DoctrineOrderItemRepository;
use App\Modules\Review\Entity\Review\Persistence\Doctrine\DoctrineReviewRepository;
use App\Modules\Review\Entity\Review\ReviewRepository;

use function DI\get;

return [
    BlogPostRepository::class        => get(DoctrineBlogPostRepository::class),
    OrderRepository::class           => get(DoctrineOrderRepository::class),
    OrderItemRepository::class       => get(DoctrineOrderItemRepository::class),
    ProductRepository::class         => get(DoctrineProductRepository::class),
    ProductCategoryRepository::class => get(DoctrineProductCategoryRepository::class),
    ReviewRepository::class          => get(DoctrineReviewRepository::class),
    UserRepository::class            => get(DoctrineUserRepository::class),
    UserTokenRepository::class       => get(DoctrineUserTokenRepository::class),
];
