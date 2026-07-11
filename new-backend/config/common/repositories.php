<?php

declare(strict_types=1);

use App\Modules\Blog\Entity\BlogPost\BlogPostRepository;
use App\Modules\Blog\Entity\BlogPost\Persistence\Doctrine\DoctrineBlogPostRepository;
use App\Modules\Bonus\Entity\BonusTransaction\BonusTransactionRepository;
use App\Modules\Bonus\Entity\BonusTransaction\Persistence\Doctrine\DoctrineBonusTransactionRepository;
use App\Modules\Media\Entity\MediaAsset\MediaAssetRepository;
use App\Modules\Media\Entity\MediaAsset\Persistence\Doctrine\DoctrineMediaAssetRepository;
use App\Modules\Order\Entity\Order\OrderRepository;
use App\Modules\Order\Entity\Order\Persistence\Doctrine\DoctrineOrderRepository;
use App\Modules\Order\Entity\OrderItem\OrderItemRepository;
use App\Modules\Order\Entity\OrderItem\Persistence\Doctrine\DoctrineOrderItemRepository;
use App\Modules\Product\Entity\Product\Persistence\Doctrine\DoctrineProductRepository;
use App\Modules\Product\Entity\Product\ProductRepository;
use App\Modules\Product\Entity\ProductCategory\Persistence\Doctrine\DoctrineProductCategoryRepository;
use App\Modules\Product\Entity\ProductCategory\ProductCategoryRepository;
use App\Modules\Review\Entity\Review\Persistence\Doctrine\DoctrineReviewRepository;
use App\Modules\Review\Entity\Review\ReviewRepository;
use App\Modules\Setting\Entity\SiteSetting\Persistence\Doctrine\DoctrineSiteSettingRepository;
use App\Modules\Setting\Entity\SiteSetting\SiteSettingRepository;
use App\Modules\User\Entity\User\Persistence\Doctrine\DoctrineUserRepository;
use App\Modules\User\Entity\User\UserRepository;
use App\Modules\User\Entity\UserProfile\Persistence\Doctrine\DoctrineUserProfileRepository;
use App\Modules\User\Entity\UserProfile\UserProfileRepository;
use App\Modules\User\Entity\UserToken\Persistence\Doctrine\DoctrineUserTokenRepository;
use App\Modules\User\Entity\UserToken\UserTokenRepository;
use App\Modules\Withdrawal\Entity\WithdrawalRequest\Persistence\Doctrine\DoctrineWithdrawalRequestRepository;
use App\Modules\Withdrawal\Entity\WithdrawalRequest\WithdrawalRequestRepository;

use function DI\get;

return [
    BonusTransactionRepository::class  => get(DoctrineBonusTransactionRepository::class),
    BlogPostRepository::class          => get(DoctrineBlogPostRepository::class),
    MediaAssetRepository::class        => get(DoctrineMediaAssetRepository::class),
    OrderRepository::class             => get(DoctrineOrderRepository::class),
    OrderItemRepository::class         => get(DoctrineOrderItemRepository::class),
    ProductRepository::class           => get(DoctrineProductRepository::class),
    ProductCategoryRepository::class   => get(DoctrineProductCategoryRepository::class),
    ReviewRepository::class            => get(DoctrineReviewRepository::class),
    SiteSettingRepository::class       => get(DoctrineSiteSettingRepository::class),
    UserRepository::class              => get(DoctrineUserRepository::class),
    UserProfileRepository::class       => get(DoctrineUserProfileRepository::class),
    UserTokenRepository::class         => get(DoctrineUserTokenRepository::class),
    WithdrawalRequestRepository::class => get(DoctrineWithdrawalRequestRepository::class),
];
