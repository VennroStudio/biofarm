<?php

declare(strict_types=1);

namespace App\Components;

use Dotenv\Dotenv;
use RuntimeException;

function env(string $name, ?string $default = null): string
{
    // From environment
    $value = getenv($name);

    if ($value !== false) {
        return $value;
    }

    $file = getenv($name . '_FILE');

    if ($file !== false) {
        return trim(file_get_contents($file));
    }

    // From .env file
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();

    if (isset($_ENV[$name]) && \is_string($_ENV[$name])) {
        return $_ENV[$name];
    }

    if (isset($_ENV[$name . '_FILE']) && \is_string($_ENV[$name . '_FILE'])) {
        $content = file_get_contents(__DIR__ . '/../' . $_ENV[$name . '_FILE']);
        if ($content !== false) {
            return trim($content);
        }
    }

    if ($default !== null) {
        return $default;
    }

    throw new RuntimeException('Undefined env ' . $name);
}
