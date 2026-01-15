<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use App\Modules\Command\BlogPost\Create\Command as BlogPostCreateCommand;
use App\Modules\Command\BlogPost\Create\Handler as BlogPostCreateHandler;
use App\Modules\Command\Order\Create\Command as OrderCreateCommand;
use App\Modules\Command\Order\Create\Handler as OrderCreateHandler;
use App\Modules\Command\OrderItem\Create\Command as OrderItemCreateCommand;
use App\Modules\Command\OrderItem\Create\Handler as OrderItemCreateHandler;
use App\Modules\Command\Product\Create\Command as ProductCreateCommand;
use App\Modules\Command\Product\Create\Handler as ProductCreateHandler;
use App\Modules\Command\Review\Create\Command as ReviewCreateCommand;
use App\Modules\Command\Review\Create\Handler as ReviewCreateHandler;
use App\Modules\Command\Settings\Create\Command as SettingsCreateCommand;
use App\Modules\Command\Settings\Create\Handler as SettingsCreateHandler;
use App\Modules\Command\User\Create\Command as UserCreateCommand;
use App\Modules\Command\User\Create\Handler as UserCreateHandler;
use App\Modules\Command\Withdrawal\Create\Command as WithdrawalCreateCommand;
use App\Modules\Command\Withdrawal\Create\Handler as WithdrawalCreateHandler;
use App\Modules\Entity\Product\ProductRepository;
use App\Modules\Entity\User\UserRepository;
use App\Modules\Entity\Order\OrderRepository;
use App\Modules\Entity\OrderItem\OrderItemRepository;
use App\Modules\Entity\Review\ReviewRepository;
use App\Modules\Entity\BlogPost\BlogPostRepository;
use App\Modules\Entity\Withdrawal\WithdrawalRepository;
use App\Modules\Entity\Settings\SettingsRepository;

// Load .env file
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
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
        __DIR__ . '/../src/Modules/Entity/Product',
        __DIR__ . '/../src/Modules/Entity/User',
        __DIR__ . '/../src/Modules/Entity/Order',
        __DIR__ . '/../src/Modules/Entity/OrderItem',
        __DIR__ . '/../src/Modules/Entity/Review',
        __DIR__ . '/../src/Modules/Entity/BlogPost',
        __DIR__ . '/../src/Modules/Entity/Withdrawal',
        __DIR__ . '/../src/Modules/Entity/Settings',
    ],
    true,
    null,
    new ArrayAdapter()
);

$connection = DriverManager::getConnection($connectionParams, $config);
$entityManager = new EntityManager($connection, $config);

// Create repositories
$productRepository = new ProductRepository($entityManager);
$userRepository = new UserRepository($entityManager);
$orderRepository = new OrderRepository($entityManager);
$orderItemRepository = new OrderItemRepository($entityManager);
$reviewRepository = new ReviewRepository($entityManager);
$blogPostRepository = new BlogPostRepository($entityManager);
$withdrawalRepository = new WithdrawalRepository($entityManager);
$settingsRepository = new SettingsRepository($entityManager);

// Create handlers
$productCreateHandler = new ProductCreateHandler($productRepository);
$userCreateHandler = new UserCreateHandler($userRepository);
$orderCreateHandler = new OrderCreateHandler($orderRepository);
$orderItemCreateHandler = new OrderItemCreateHandler($orderItemRepository);
$reviewCreateHandler = new ReviewCreateHandler($reviewRepository);
$blogPostCreateHandler = new BlogPostCreateHandler($blogPostRepository);
$withdrawalCreateHandler = new WithdrawalCreateHandler($withdrawalRepository);
$settingsCreateHandler = new SettingsCreateHandler($settingsRepository);

echo "Loading fixtures...\n";

// In Docker container, frontend is mounted at /app-frontend
$basePath = '/app-frontend/src/data';
if (!is_dir($basePath)) {
    // Fallback for local execution
    $basePath = __DIR__ . '/../../frontend/src/data';
}
if (!is_dir($basePath)) {
    die("Cannot find fixtures directory. Tried: /app-frontend/src/data and " . __DIR__ . '/../../frontend/src/data' . "\n");
}

// Load products
$productsData = json_decode(file_get_contents($basePath . '/products.json'), true);
foreach ($productsData['products'] as $productData) {
    $productCreateHandler->handle(new ProductCreateCommand(
        slug: $productData['slug'],
        name: $productData['name'],
        categoryId: $productData['category_id'],
        price: $productData['price'],
        image: $productData['image'],
        weight: $productData['weight'],
        description: $productData['description'],
        shortDescription: $productData['short_description'],
        oldPrice: $productData['old_price'] ?? null,
        images: $productData['images'] ?? null,
        badge: $productData['badge'] ?? null,
        ingredients: $productData['ingredients'] ?? null,
        features: $productData['features'] ?? null,
        wbLink: $productData['wb_link'] ?? null,
        ozonLink: $productData['ozon_link'] ?? null,
        isActive: $productData['is_active'] ?? true,
    ));
}
$entityManager->flush();
echo "Loaded " . count($productsData['products']) . " products\n";

// Load users
$usersData = json_decode(file_get_contents($basePath . '/users.json'), true);
$userIds = [];
foreach ($usersData['users'] as $idx => $userData) {
    $user = $userCreateHandler->handle(new UserCreateCommand(
        email: $userData['email'],
        name: $userData['name'],
        passwordHash: password_hash($userData['password_hash'], PASSWORD_DEFAULT),
        phone: $userData['phone'] ?? null,
        referredBy: $userData['referred_by'] ?? null,
        isPartner: $userData['is_partner'] ?? false,
        isActive: $userData['is_active'] ?? true,
    ));
    $entityManager->flush();
    $userIds[$userData['id']] = $user->getId();
}
echo "Loaded " . count($usersData['users']) . " users\n";

// Load orders and order items
$ordersData = json_decode(file_get_contents($basePath . '/orders.json'), true);
foreach ($ordersData['orders'] as $orderData) {
    $order = $orderCreateHandler->handle(new OrderCreateCommand(
        orderId: $orderData['id'],
        userId: $userIds[$orderData['user_id']] ?? 1,
        total: $orderData['total'],
        shippingAddress: $orderData['shipping_address'],
        paymentMethod: $orderData['payment_method'],
        bonusUsed: $orderData['bonus_used'] ?? 0,
        status: $orderData['status'],
        paymentStatus: $orderData['payment_status'],
    ));
    $entityManager->flush();

    // Create order items
    foreach ($orderData['items'] as $itemData) {
        $orderItemCreateHandler->handle(new OrderItemCreateCommand(
            orderId: $order->getId(),
            productId: $itemData['product_id'],
            productName: $itemData['product_name'],
            price: $itemData['price'],
            quantity: $itemData['quantity'],
        ));
    }
    $entityManager->flush();
}
echo "Loaded " . count($ordersData['orders']) . " orders\n";

// Load reviews
$reviewsData = json_decode(file_get_contents($basePath . '/reviews.json'), true);
foreach ($reviewsData['reviews'] as $reviewData) {
    $reviewCreateHandler->handle(new ReviewCreateCommand(
        reviewId: $reviewData['id'],
        productId: $reviewData['product_id'],
        userName: $reviewData['user_name'],
        rating: $reviewData['rating'],
        text: $reviewData['text'],
        source: $reviewData['source'],
        userId: $reviewData['user_id'] ?? null,
        images: $reviewData['images'] ?? null,
        isApproved: $reviewData['is_approved'] ?? false,
    ));
}
$entityManager->flush();
echo "Loaded " . count($reviewsData['reviews']) . " reviews\n";

// Load blog posts
$blogData = json_decode(file_get_contents($basePath . '/blog.json'), true);
foreach ($blogData['posts'] as $postData) {
    $blogPostCreateHandler->handle(new BlogPostCreateCommand(
        slug: $postData['slug'],
        title: $postData['title'],
        excerpt: $postData['excerpt'],
        content: $postData['content'],
        image: $postData['image'],
        categoryId: $postData['category_id'],
        authorId: $postData['author_id'],
        readTime: $postData['read_time'],
        isPublished: $postData['is_published'] ?? true,
    ));
}
$entityManager->flush();
echo "Loaded " . count($blogData['posts']) . " blog posts\n";

// Load withdrawals
$withdrawalsData = json_decode(file_get_contents($basePath . '/withdrawals.json'), true);
foreach ($withdrawalsData['withdrawals'] as $withdrawalData) {
    $withdrawalCreateHandler->handle(new WithdrawalCreateCommand(
        withdrawalId: $withdrawalData['id'],
        userId: $userIds[$withdrawalData['user_id']] ?? 2,
        amount: $withdrawalData['amount'],
        status: $withdrawalData['status'],
    ));
}
$entityManager->flush();
echo "Loaded " . count($withdrawalsData['withdrawals']) . " withdrawals\n";

// Load settings
$settingsData = json_decode(file_get_contents($basePath . '/settings.json'), true);
$settingsCreateHandler->handle(new SettingsCreateCommand(
    key: 'referralPercent',
    value: (string)$settingsData['referralPercent'],
));
$settingsCreateHandler->handle(new SettingsCreateCommand(
    key: 'orderBonusEnabled',
    value: $settingsData['orderBonusEnabled'] ? '1' : '0',
));
$settingsCreateHandler->handle(new SettingsCreateCommand(
    key: 'orderBonusPercent',
    value: (string)$settingsData['orderBonusPercent'],
));
$entityManager->flush();
echo "Loaded settings\n";

echo "Fixtures loaded successfully!\n";
