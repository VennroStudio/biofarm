<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Application;
use App\Console\OtherDbCommand;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$entityPaths = [
    __DIR__ . '/../src/Modules/Entity/Product',
    __DIR__ . '/../src/Modules/Entity/Category',
    __DIR__ . '/../src/Modules/Entity/User',
    __DIR__ . '/../src/Modules/Entity/Admin',
    __DIR__ . '/../src/Modules/Entity/Order',
    __DIR__ . '/../src/Modules/Entity/OrderItem',
    __DIR__ . '/../src/Modules/Entity/Review',
    __DIR__ . '/../src/Modules/Entity/BlogPost',
    __DIR__ . '/../src/Modules/Entity/Withdrawal',
    __DIR__ . '/../src/Modules/Entity/Settings',
];
$ormConfig = ORMSetup::createAttributeMetadataConfiguration($entityPaths, true, null, new ArrayAdapter());

// Основная БД (DB_*)
$mainParams = [
    'driver' => 'pdo_mysql',
    'host' => $_ENV['DB_HOST'],
    'port' => (int)($_ENV['DB_PORT'] ?? 3306),
    'user' => $_ENV['DB_USER'],
    'password' => $_ENV['DB_PASSWORD'],
    'dbname' => $_ENV['DB_NAME'],
    'charset' => 'utf8mb4',
];
$mainConnection = DriverManager::getConnection($mainParams, $ormConfig);
$mainEm = new EntityManager($mainConnection, $ormConfig);

// Вторая БД (DB2_*)
$otherDbParams = [
    'driver' => 'pdo_mysql',
    'host' => $_ENV['DB2_HOST'] ?? '',
    'port' => (int)($_ENV['DB2_PORT'] ?? 3306),
    'user' => $_ENV['DB2_USER'] ?? '',
    'password' => $_ENV['DB2_PASSWORD'] ?? '',
    'dbname' => $_ENV['DB2_NAME'] ?? '',
    'charset' => 'utf8mb4',
];

if (empty($otherDbParams['host']) || empty($otherDbParams['dbname'])) {
    fwrite(STDERR, "Задайте переменные второй БД в .env: DB2_HOST, DB2_PORT, DB2_USER, DB2_PASSWORD, DB2_NAME\n");
    exit(1);
}

$otherConnection = DriverManager::getConnection($otherDbParams);

$app = new Application('Biofarm Console');
$app->add(new OtherDbCommand($mainEm, $otherConnection));
$app->run();
