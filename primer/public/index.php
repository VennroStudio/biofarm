<?php

declare(strict_types=1);

use DuckBug\Core\ProviderSetup;
use DuckBug\Duck;
use DuckBug\Providers\DuckBugProvider;
use Psr\Container\ContainerInterface;

use function App\Components\env;

date_default_timezone_set('UTC');
http_response_code(500);

require __DIR__ . '/../vendor/autoload.php';

Duck::wake([
    new ProviderSetup(
        provider: DuckBugProvider::create(
            dsn: env('DUCKBUG_DSN')
        ),
        enabledDebug: env('APP_ENV') === 'dev'
    ),
]);

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../config/container.php';

$app = (require __DIR__ . '/../config/app.php')($container);
$app->run();
