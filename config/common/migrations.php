<?php

declare(strict_types=1);

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
use Doctrine\Migrations\Tools\Console\Command\UpToDateCommand;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;

return [
    DependencyFactory::class => static function (ContainerInterface $container): DependencyFactory {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);

        $configuration = new Configuration();
        $configuration->addMigrationsDirectory('App\Migrations', __DIR__ . '/../../src/Migrations');
        $configuration->setCheckDatabasePlatform(false);

        $storageConfiguration = new TableMetadataStorageConfiguration();
        $storageConfiguration->setTableName('migrations');

        $configuration->setMetadataStorageConfiguration($storageConfiguration);

        return DependencyFactory::fromEntityManager(
            new ExistingConfiguration($configuration),
            new ExistingEntityManager($entityManager)
        );
    },

    ExecuteCommand::class => static function (ContainerInterface $container): ExecuteCommand {
        /** @var DependencyFactory $factory */
        $factory = $container->get(DependencyFactory::class);
        return new ExecuteCommand($factory);
    },

    MigrateCommand::class => static function (ContainerInterface $container): MigrateCommand {
        /** @var DependencyFactory $factory */
        $factory = $container->get(DependencyFactory::class);
        return new MigrateCommand($factory);
    },

    LatestCommand::class => static function (ContainerInterface $container): LatestCommand {
        /** @var DependencyFactory $factory */
        $factory = $container->get(DependencyFactory::class);
        return new LatestCommand($factory);
    },

    ListCommand::class => static function (ContainerInterface $container): ListCommand {
        /** @var DependencyFactory $factory */
        $factory = $container->get(DependencyFactory::class);
        return new ListCommand($factory);
    },

    StatusCommand::class => static function (ContainerInterface $container): StatusCommand {
        /** @var DependencyFactory $factory */
        $factory = $container->get(DependencyFactory::class);
        return new StatusCommand($factory);
    },

    UpToDateCommand::class => static function (ContainerInterface $container): UpToDateCommand {
        /** @var DependencyFactory $factory */
        $factory = $container->get(DependencyFactory::class);
        return new UpToDateCommand($factory);
    },

    DiffCommand::class => static function (ContainerInterface $container): DiffCommand {
        /** @var DependencyFactory $factory */
        $factory = $container->get(DependencyFactory::class);
        return new DiffCommand($factory);
    },

    GenerateCommand::class => static function (ContainerInterface $container): GenerateCommand {
        /** @var DependencyFactory $factory */
        $factory = $container->get(DependencyFactory::class);
        return new GenerateCommand($factory);
    },
];
