<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\Migrations\Tools\Console\Command\GenerateCommand;
use Doctrine\Migrations\Tools\Console\Command\LatestCommand;
use Doctrine\Migrations\Tools\Console\Command\ListCommand;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\Migrations\Tools\Console\Command\SyncMetadataCommand;
use Doctrine\Migrations\Tools\Console\Command\UpToDateCommand;
use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use App\Console\FixturesLoadCommand;
use Symfony\Component\Console\Application;

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

// Configure migrations
$migrationConfig = new Configuration();
$migrationConfig->addMigrationsDirectory('App\Migrations', __DIR__ . '/../src/Migrations');
$migrationConfig->setCheckDatabasePlatform(false);

$storageConfiguration = new TableMetadataStorageConfiguration();
$storageConfiguration->setTableName('migrations');
$migrationConfig->setMetadataStorageConfiguration($storageConfiguration);

$dependencyFactory = DependencyFactory::fromEntityManager(
    new ExistingConfiguration($migrationConfig),
    new ExistingEntityManager($entityManager)
);

// Create console application
$cli = new Application('Migrations');

// Add migration commands
$cli->add(new ExecuteCommand($dependencyFactory));
$cli->add(new MigrateCommand($dependencyFactory));
$cli->add(new LatestCommand($dependencyFactory));
$cli->add(new ListCommand($dependencyFactory));
$cli->add(new StatusCommand($dependencyFactory));
$cli->add(new UpToDateCommand($dependencyFactory));
$cli->add(new DiffCommand($dependencyFactory));
$cli->add(new GenerateCommand($dependencyFactory));
$cli->add(new SyncMetadataCommand($dependencyFactory));

// Add ORM commands
$entityManagerProvider = new SingleManagerProvider($entityManager);
$cli->add(new ValidateSchemaCommand($entityManagerProvider));

// Add custom commands - LoadFixturesCommand will be added via DI in actual app

$cli->run();
