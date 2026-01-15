<?php

declare(strict_types=1);

use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\Command\GenerateCommand;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Psr\Container\ContainerInterface;
use ZayMedia\Shared\Console\FixturesLoadCommand;

return [
    FixturesLoadCommand::class => static function (ContainerInterface $container) {
        /**
         * @psalm-suppress MixedArrayAccess
         * @var array{fixture_paths:string[]} $config
         */
        $config = $container->get('config')['console'];

        return new FixturesLoadCommand(
            $container->get(EntityManagerInterface::class),
            $config['fixture_paths'],
        );
    },

    DropCommand::class => static fn (ContainerInterface $container): DropCommand => new DropCommand($container->get(EntityManagerProvider::class)),

    'config' => [
        'console' => [
            'commands' => [
                FixturesLoadCommand::class,

                DropCommand::class,

                DiffCommand::class,
                GenerateCommand::class,
            ],
            'fixture_paths' => [
                __DIR__ . '/../../src/Modules/Identity/Fixture',
            ],
        ],
    ],
];
