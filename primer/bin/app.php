#!/usr/bin/env php
<?php

declare(strict_types=1);

use DuckBug\Core\ProviderSetup;
use DuckBug\Duck;
use DuckBug\Providers\DuckBugProvider;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

use function App\Components\env;

require __DIR__ . '/../vendor/autoload.php';

$duck = Duck::wake([
    new ProviderSetup(
        provider: DuckBugProvider::create(
            dsn: env('DUCKBUG_DSN'),
        ),
        enabledDebug: env('APP_ENV') === 'dev'
    ),
]);

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../config/container.php';

$cli = new Application('Console');

if (getenv('DUCKBUG_DSN') !== false) {
    $cli->setCatchExceptions(false);
}

try {
    /**
     * @var string[] $commands
     * @psalm-suppress MixedArrayAccess
     */
    $commands = $container->get('config')['console']['commands'];

    foreach ($commands as $name) {
        /** @var Command $command */
        $command = $container->get($name);
        $cli->add($command);
    }

    $cli->run();
} catch (Throwable $e) {
    $duck->quack($e, ['source' => 'console']);

    throw $e;
}
