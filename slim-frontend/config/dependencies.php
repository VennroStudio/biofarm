<?php

declare(strict_types=1);

use function App\Components\env;

$definitions = [];
$paths = [
    __DIR__ . '/common/*.php',
    __DIR__ . '/' . env('APP_ENV', 'dev') . '/*.php',
];

foreach ($paths as $path) {
    foreach (glob($path) ?: [] as $file) {
        /** @psalm-suppress UnresolvableInclude */
        $definitions = array_replace_recursive($definitions, require $file);
    }
}

return $definitions;
