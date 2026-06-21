#!/usr/bin/env php
<?php

declare(strict_types=1);

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

require __DIR__ . '/../vendor/autoload.php';

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../config/container.php';

$cli = new Application('Console');

try {
    /** @var array{console: array{commands: list<class-string>}} $config */
    $config = $container->get('config');
    $commandNames = $config['console']['commands'];

    /** @var list<Command> $commands */
    $commands = array_map(
        $container->get(...),
        $commandNames
    );

    $cli->addCommands($commands);

    $cli->run();
} catch (ContainerExceptionInterface|NotFoundExceptionInterface $e) {
    echo 'Container error: ' . $e->getMessage();
    exit(1);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
    exit(1);
}
