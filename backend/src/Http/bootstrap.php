<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use App\Modules\Entity\Product\ProductRepository;
use App\Modules\Entity\Category\CategoryRepository;
use App\Modules\Entity\User\UserRepository;
use App\Modules\Entity\Order\OrderRepository;
use App\Modules\Entity\OrderItem\OrderItemRepository;
use App\Modules\Entity\Review\ReviewRepository;
use App\Modules\Entity\BlogPost\BlogPostRepository;
use App\Modules\Entity\Withdrawal\WithdrawalRepository;
use App\Modules\Entity\Settings\SettingsRepository;
use App\Modules\Query\Products\GetAll\Fetcher as ProductsGetAllFetcher;
use App\Modules\Query\Products\GetBySlug\Fetcher as ProductsGetBySlugFetcher;
use App\Modules\Query\Users\GetById\Fetcher as UsersGetByIdFetcher;
use App\Modules\Query\Users\GetByEmail\Fetcher as UsersGetByEmailFetcher;
use App\Modules\Query\Users\GetAll\Fetcher as UsersGetAllFetcher;
use App\Modules\Query\Users\GetReferralInfo\Fetcher as UsersGetReferralInfoFetcher;
use App\Modules\Query\Orders\GetAll\Fetcher as OrdersGetAllFetcher;
use App\Modules\Query\Orders\GetByUserId\Fetcher as OrdersGetByUserIdFetcher;
use App\Modules\Query\Orders\GetByReferrerId\Fetcher as OrdersGetByReferrerIdFetcher;
use App\Modules\Query\Orders\GetById\Fetcher as OrdersGetByIdFetcher;
use App\Modules\Query\Reviews\GetAll\Fetcher as ReviewsGetAllFetcher;
use App\Modules\Query\Reviews\GetByProductId\Fetcher as ReviewsGetByProductIdFetcher;
use App\Modules\Query\BlogPosts\GetAll\Fetcher as BlogPostsGetAllFetcher;
use App\Modules\Query\BlogPosts\GetBySlug\Fetcher as BlogPostsGetBySlugFetcher;
use App\Modules\Query\Categories\GetAll\Fetcher as CategoriesGetAllFetcher;
use App\Modules\Query\Withdrawals\GetAll\Fetcher as WithdrawalsGetAllFetcher;
use App\Modules\Query\Withdrawals\GetByUserId\Fetcher as WithdrawalsGetByUserIdFetcher;
use App\Modules\Command\Product\Create\Handler as ProductCreateHandler;
use App\Modules\Command\Product\Update\Handler as ProductUpdateHandler;
use App\Modules\Command\Product\Delete\Handler as ProductDeleteHandler;
use App\Modules\Command\User\Create\Handler as UserCreateHandler;
use App\Modules\Command\User\Update\Handler as UserUpdateHandler;
use App\Modules\Command\Order\Create\Handler as OrderCreateHandler;
use App\Modules\Command\Order\UpdateStatus\Handler as OrderUpdateStatusHandler;
use App\Modules\Command\Order\UpdatePaymentStatus\Handler as OrderUpdatePaymentStatusHandler;
use App\Modules\Command\Review\Create\Handler as ReviewCreateHandler;
use App\Modules\Command\Review\Approve\Handler as ReviewApproveHandler;
use App\Modules\Command\Review\Delete\Handler as ReviewDeleteHandler;
use App\Modules\Command\Review\Update\Handler as ReviewUpdateHandler;
use App\Modules\Command\BlogPost\Create\Handler as BlogPostCreateHandler;
use App\Modules\Command\BlogPost\Update\Handler as BlogPostUpdateHandler;
use App\Modules\Command\BlogPost\Delete\Handler as BlogPostDeleteHandler;
use App\Modules\Command\Category\Create\Handler as CategoryCreateHandler;
use App\Modules\Command\Category\Update\Handler as CategoryUpdateHandler;
use App\Modules\Command\Category\Delete\Handler as CategoryDeleteHandler;
use App\Modules\Command\Withdrawal\Create\Handler as WithdrawalCreateHandler;
use App\Modules\Command\Withdrawal\UpdateStatus\Handler as WithdrawalUpdateStatusHandler;

// Load .env file
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Create database connection
$connectionParams = [
    'driver' => 'pdo_mysql',
    'host' => $_ENV['DB_HOST'],
    'port' => (int)$_ENV['DB_PORT'],
    'user' => $_ENV['DB_USER'],
    'password' => $_ENV['DB_PASSWORD'],
    'dbname' => $_ENV['DB_NAME'],
    'charset' => 'utf8mb4',
];

// Setup Doctrine
$config = ORMSetup::createAttributeMetadataConfiguration(
    [
        __DIR__ . '/../Modules/Entity/Product',
        __DIR__ . '/../Modules/Entity/Category',
        __DIR__ . '/../Modules/Entity/User',
        __DIR__ . '/../Modules/Entity/Order',
        __DIR__ . '/../Modules/Entity/OrderItem',
        __DIR__ . '/../Modules/Entity/Review',
        __DIR__ . '/../Modules/Entity/BlogPost',
        __DIR__ . '/../Modules/Entity/Withdrawal',
        __DIR__ . '/../Modules/Entity/Settings',
    ],
    true,
    null,
    new ArrayAdapter()
);

$connection = DriverManager::getConnection($connectionParams, $config);
$entityManager = new EntityManager($connection, $config);

// Create repositories
$productRepository = new ProductRepository($entityManager);
$categoryRepository = new CategoryRepository($entityManager);
$userRepository = new UserRepository($entityManager);
$orderRepository = new OrderRepository($entityManager);
$orderItemRepository = new OrderItemRepository($entityManager);
$reviewRepository = new ReviewRepository($entityManager);
$blogPostRepository = new BlogPostRepository($entityManager);
$withdrawalRepository = new WithdrawalRepository($entityManager);
$settingsRepository = new SettingsRepository($entityManager);

// Create Query Fetchers
$productsGetAllFetcher = new ProductsGetAllFetcher($productRepository);
$productsGetBySlugFetcher = new ProductsGetBySlugFetcher($productRepository);
    $usersGetByIdFetcher = new UsersGetByIdFetcher($userRepository);
    $usersGetByEmailFetcher = new UsersGetByEmailFetcher($userRepository);
    $usersGetAllFetcher = new UsersGetAllFetcher($userRepository);
    $usersGetReferralInfoFetcher = new UsersGetReferralInfoFetcher($userRepository, $orderRepository, $settingsRepository);
$ordersGetAllFetcher = new OrdersGetAllFetcher($orderRepository);
$ordersGetByUserIdFetcher = new OrdersGetByUserIdFetcher($orderRepository);
$ordersGetByReferrerIdFetcher = new OrdersGetByReferrerIdFetcher($orderRepository, $userRepository);
$ordersGetByIdFetcher = new OrdersGetByIdFetcher($orderRepository);
$reviewsGetAllFetcher = new ReviewsGetAllFetcher($reviewRepository);
$reviewsGetByProductIdFetcher = new ReviewsGetByProductIdFetcher($reviewRepository);
$blogPostsGetAllFetcher = new BlogPostsGetAllFetcher($blogPostRepository);
$blogPostsGetBySlugFetcher = new BlogPostsGetBySlugFetcher($blogPostRepository);
$categoriesGetAllFetcher = new CategoriesGetAllFetcher($categoryRepository);
$withdrawalsGetAllFetcher = new WithdrawalsGetAllFetcher($withdrawalRepository);
$withdrawalsGetByUserIdFetcher = new WithdrawalsGetByUserIdFetcher($withdrawalRepository);

// Create Command Handlers
$productCreateHandler = new ProductCreateHandler($productRepository);
$productUpdateHandler = new ProductUpdateHandler($productRepository);
$productDeleteHandler = new ProductDeleteHandler($productRepository);
$userCreateHandler = new UserCreateHandler($userRepository);
$userUpdateHandler = new UserUpdateHandler($userRepository);
$orderCreateHandler = new OrderCreateHandler($orderRepository);
$orderItemCreateHandler = new \App\Modules\Command\OrderItem\Create\Handler($orderItemRepository);
$orderUpdateStatusHandler = new OrderUpdateStatusHandler($orderRepository);
$orderUpdatePaymentStatusHandler = new OrderUpdatePaymentStatusHandler($orderRepository, $userRepository, $settingsRepository, $entityManager);
    $reviewCreateHandler = new ReviewCreateHandler($reviewRepository);
    $reviewApproveHandler = new ReviewApproveHandler($reviewRepository);
    $reviewDeleteHandler = new ReviewDeleteHandler($reviewRepository);
    $reviewUpdateHandler = new ReviewUpdateHandler($reviewRepository);
$blogPostCreateHandler = new BlogPostCreateHandler($blogPostRepository);
$blogPostUpdateHandler = new BlogPostUpdateHandler($blogPostRepository);
$blogPostDeleteHandler = new BlogPostDeleteHandler($blogPostRepository);
$categoryCreateHandler = new CategoryCreateHandler($categoryRepository);
$categoryUpdateHandler = new CategoryUpdateHandler($categoryRepository);
$categoryDeleteHandler = new CategoryDeleteHandler($categoryRepository, $productRepository);
$withdrawalCreateHandler = new WithdrawalCreateHandler($withdrawalRepository);
$withdrawalUpdateStatusHandler = new WithdrawalUpdateStatusHandler($withdrawalRepository);

return [
    'em' => $entityManager,
    'orderItemRepository' => $orderItemRepository,
    'fetchers' => [
        'productsGetAll' => $productsGetAllFetcher,
        'productsGetBySlug' => $productsGetBySlugFetcher,
        'usersGetById' => $usersGetByIdFetcher,
        'usersGetByEmail' => $usersGetByEmailFetcher,
        'usersGetAll' => $usersGetAllFetcher,
        'usersGetReferralInfo' => $usersGetReferralInfoFetcher,
        'ordersGetAll' => $ordersGetAllFetcher,
        'ordersGetByUserId' => $ordersGetByUserIdFetcher,
        'ordersGetByReferrerId' => $ordersGetByReferrerIdFetcher,
        'ordersGetById' => $ordersGetByIdFetcher,
        'reviewsGetAll' => $reviewsGetAllFetcher,
        'reviewsGetByProductId' => $reviewsGetByProductIdFetcher,
        'blogPostsGetAll' => $blogPostsGetAllFetcher,
        'blogPostsGetBySlug' => $blogPostsGetBySlugFetcher,
        'categoriesGetAll' => $categoriesGetAllFetcher,
        'withdrawalsGetAll' => $withdrawalsGetAllFetcher,
        'withdrawalsGetByUserId' => $withdrawalsGetByUserIdFetcher,
    ],
        'handlers' => [
            'productCreate' => $productCreateHandler,
            'productUpdate' => $productUpdateHandler,
            'productDelete' => $productDeleteHandler,
        'userCreate' => $userCreateHandler,
        'userUpdate' => $userUpdateHandler,
        'orderCreate' => $orderCreateHandler,
        'orderItemCreate' => $orderItemCreateHandler,
        'orderUpdateStatus' => $orderUpdateStatusHandler,
        'orderUpdatePaymentStatus' => $orderUpdatePaymentStatusHandler,
        'reviewCreate' => $reviewCreateHandler,
        'reviewApprove' => $reviewApproveHandler,
        'reviewDelete' => $reviewDeleteHandler,
        'reviewUpdate' => $reviewUpdateHandler,
        'blogPostCreate' => $blogPostCreateHandler,
        'blogPostUpdate' => $blogPostUpdateHandler,
        'blogPostDelete' => $blogPostDeleteHandler,
        'categoryCreate' => $categoryCreateHandler,
        'categoryUpdate' => $categoryUpdateHandler,
        'categoryDelete' => $categoryDeleteHandler,
        'withdrawalCreate' => $withdrawalCreateHandler,
        'withdrawalUpdateStatus' => $withdrawalUpdateStatusHandler,
    ],
];
