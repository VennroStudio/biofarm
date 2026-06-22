<?php

declare(strict_types=1);

namespace App\Components;

use Dotenv\Dotenv;
use RuntimeException;

function env(string $name, ?string $default = null): string
{
    static $loaded = false;

    if (!$loaded) {
        Dotenv::createImmutable(__DIR__ . '/../../')->safeLoad();
        $loaded = true;
    }

    $value = getenv($name);
    if ($value !== false) {
        return $value;
    }

    if (\array_key_exists($name, $_ENV)) {
        return (string)$_ENV[$name];
    }

    $fileValue = getenv($name . '_FILE');
    if ($fileValue === false && \array_key_exists($name . '_FILE', $_ENV)) {
        $fileValue = $_ENV[$name . '_FILE'];
    }

    if ($fileValue !== false) {
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

function env_bool(string $name, ?bool $default = null): bool
{
    $value = env($name, $default === null ? null : ($default ? '1' : '0'));
    $result = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

    if ($result === null) {
        throw new RuntimeException("Env '{$name}' must be boolean.");
    }

    return $result;
}

function env_int(string $name, ?int $default = null): int
{
    $value = env($name, $default === null ? null : (string)$default);
    $result = filter_var($value, FILTER_VALIDATE_INT);

    if ($result === false) {
        throw new RuntimeException("Env '{$name}' must be integer.");
    }

    return $result;
}

function env_float(string $name, ?float $default = null): float
{
    $value = env($name, $default === null ? null : (string)$default);

    if (!is_numeric($value)) {
        throw new RuntimeException("Env '{$name}' must be float.");
    }

    return (float)$value;
}
