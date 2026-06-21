<?php

declare(strict_types=1);

use App\Console\FixturesLoadCommand;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\Command\GenerateCommand;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Psr\Container\ContainerInterface;

return [
    EntityManagerProvider::class => static function (ContainerInterface $container): EntityManagerProvider {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        return new SingleManagerProvider($entityManager);
    },

    FixturesLoadCommand::class => static function (ContainerInterface $container): FixturesLoadCommand {
        /** @var array{console: array{fixture_paths: string[]}} $fullConfig */
        $fullConfig = $container->get('config');
        $config = $fullConfig['console'];

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);

        return new FixturesLoadCommand(
            $entityManager,
            $config['fixture_paths'],
        );
    },

    DropCommand::class => static function (ContainerInterface $container): DropCommand {
        /** @var EntityManagerProvider $entityManagerProvider */
        $entityManagerProvider = $container->get(EntityManagerProvider::class);
        return new DropCommand($entityManagerProvider);
    },

    'config' => [
        'console' => [
            'commands' => [
                FixturesLoadCommand::class,
                DropCommand::class,
                DiffCommand::class,
                GenerateCommand::class,
                MigrateCommand::class,
            ],
            'fixture_paths' => [],
        ],
    ],
];
