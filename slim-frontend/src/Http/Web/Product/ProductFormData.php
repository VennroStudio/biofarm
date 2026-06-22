<?php

declare(strict_types=1);

namespace App\Http\Web\Product;

use Psr\Http\Message\ServerRequestInterface;

final readonly class ProductFormData
{
    /**
     * @return array<string, string>
     */
    public static function fromRequest(ServerRequestInterface $request): array
    {
        $parsedBody = $request->getParsedBody();

        if (!\is_array($parsedBody)) {
            return [];
        }

        /** @var array<array-key, array<array-key, mixed>|bool|float|int|object|string|null> $body */
        $body = $parsedBody;

        $data = [];
        foreach ($body as $key => $value) {
            if (\is_string($key) && (\is_string($value) || is_numeric($value))) {
                $data[$key] = (string)$value;
            }
        }

        return $data;
    }

    /**
     * @param array<string, string> $data
     */
    public static function string(array $data, string $key, string $default = ''): string
    {
        return trim($data[$key] ?? $default);
    }

    /**
     * @param array<string, string> $data
     */
    public static function stringOrNull(array $data, string $key): ?string
    {
        $value = self::string($data, $key);

        return $value === '' ? null : $value;
    }

    /**
     * @param array<string, string> $data
     */
    public static function int(array $data, string $key, int $default = 0): int
    {
        $value = self::string($data, $key);

        return $value === '' ? $default : (int)$value;
    }

    /**
     * @param array<string, string> $data
     */
    public static function intOrNull(array $data, string $key): ?int
    {
        $value = self::string($data, $key);

        return $value === '' ? null : (int)$value;
    }

    /**
     * @param array<string, string> $data
     */
    public static function float(array $data, string $key, float $default = 0): float
    {
        $value = self::string($data, $key);

        return $value === '' ? $default : (float)$value;
    }

    /**
     * @param array<string, string> $data
     */
    public static function floatOrNull(array $data, string $key): ?float
    {
        $value = self::string($data, $key);

        return $value === '' ? null : (float)$value;
    }
}
