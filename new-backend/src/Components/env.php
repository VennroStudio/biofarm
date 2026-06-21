<?php

declare(strict_types=1);

namespace App\Components;

use Dotenv\Dotenv;
use RuntimeException;

function env(string $name, ?string $default = null): string
{
    static $loaded = false;

    if (!$loaded) {
        Dotenv::createImmutable(__DIR__ . '/../../')->load();
        $loaded = true;
    }

    $value = getenv($name) ?: ($_ENV[$name] ?? null);
    if ($value !== null && $value !== false) {
        return (string)$value;
    }

    $fileValue = getenv($name . '_FILE') ?: ($_ENV[$name . '_FILE'] ?? null);
    if ($fileValue !== null && $fileValue !== false) {
        $content = file_get_contents((string)$fileValue);
        if ($content === false) {
            throw new RuntimeException("Cannot read file for env '{$name}': {$fileValue}");
        }
        return trim($content);
    }

    if ($default !== null) {
        return $default;
    }

    throw new RuntimeException("Undefined env '{$name}'.");
}
