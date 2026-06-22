<?php

declare(strict_types=1);

namespace App\Components\Api;

final readonly class ApiPayload
{
    /**
     * @param array<array-key, mixed> $payload
     * @return array<array-key, mixed>
     */
    public static function extractData(array $payload): array
    {
        if (!\array_key_exists('data', $payload)) {
            return $payload;
        }

        if (!\is_array($payload['data'])) {
            throw ApiException::invalidPayload('Expected "data" to be an array.');
        }

        return $payload['data'];
    }

    /**
     * @param array<array-key, mixed> $payload
     * @return list<array<array-key, mixed>>
     */
    public static function extractDataList(array $payload): array
    {
        $data = self::extractData($payload);

        $items = [];
        foreach ($data as $index => $item) {
            if (!\is_array($item)) {
                throw ApiException::invalidPayload("Expected data item '{$index}' to be an object.");
            }

            $items[] = $item;
        }

        /** @var list<array<array-key, mixed>> $items */
        return $items;
    }

    /**
     * @param array<array-key, mixed> $payload
     * @return list<string>
     */
    public static function extractStringList(array $payload): array
    {
        $data = self::extractData($payload);

        $items = [];
        foreach ($data as $index => $item) {
            if (!\is_string($item)) {
                throw ApiException::invalidPayload("Expected data item '{$index}' to be a string.");
            }

            $items[] = $item;
        }

        /** @var list<string> $items */
        return $items;
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function requireString(array $payload, string $key): string
    {
        $value = self::requireValue($payload, $key);
        if (!\is_string($value)) {
            throw ApiException::invalidPayload("Expected '{$key}' to be a string.");
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function optionalString(array $payload, string $key, string $default = ''): string
    {
        if (!\array_key_exists($key, $payload) || $payload[$key] === null) {
            return $default;
        }

        return self::requireString($payload, $key);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function requireInt(array $payload, string $key): int
    {
        $value = self::requireValue($payload, $key);
        if (!\is_int($value)) {
            throw ApiException::invalidPayload("Expected '{$key}' to be an integer.");
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function optionalInt(array $payload, string $key, int $default = 0): int
    {
        if (!\array_key_exists($key, $payload) || $payload[$key] === null) {
            return $default;
        }

        return self::requireInt($payload, $key);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function requireFloat(array $payload, string $key): float
    {
        $value = self::requireValue($payload, $key);
        if (!\is_float($value) && !\is_int($value)) {
            throw ApiException::invalidPayload("Expected '{$key}' to be a number.");
        }

        return (float)$value;
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function optionalFloat(array $payload, string $key, float $default = 0.0): float
    {
        if (!\array_key_exists($key, $payload) || $payload[$key] === null) {
            return $default;
        }

        return self::requireFloat($payload, $key);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function optionalBool(array $payload, string $key, bool $default = false): bool
    {
        if (!\array_key_exists($key, $payload) || $payload[$key] === null) {
            return $default;
        }

        if (!\is_bool($payload[$key])) {
            throw ApiException::invalidPayload("Expected '{$key}' to be boolean.");
        }

        return $payload[$key];
    }

    /**
     * @param array<array-key, mixed> $payload
     * @return array<array-key, mixed>
     */
    public static function optionalArray(array $payload, string $key): array
    {
        if (!\array_key_exists($key, $payload) || $payload[$key] === null) {
            return [];
        }

        if (!\is_array($payload[$key])) {
            throw ApiException::invalidPayload("Expected '{$key}' to be an array.");
        }

        return $payload[$key];
    }

    /**
     * @param array<array-key, mixed> $payload
     * @return array<string, bool|float|int|string>
     */
    public static function optionalScalarMap(array $payload, string $key): array
    {
        $items = self::optionalArray($payload, $key);

        $result = [];
        foreach ($items as $itemKey => $itemValue) {
            if (!\is_string($itemKey) || !\is_bool($itemValue) && !\is_float($itemValue) && !\is_int($itemValue) && !\is_string($itemValue)) {
                throw ApiException::invalidPayload("Expected '{$key}' to be a scalar map.");
            }

            $result[$itemKey] = $itemValue;
        }

        return $result;
    }

    /**
     * @param array<array-key, mixed> $payload
     * @return list<int>
     */
    public static function optionalIntList(array $payload, string $key): array
    {
        $items = self::optionalArray($payload, $key);
        $result = [];

        foreach ($items as $index => $item) {
            if (!\is_int($item)) {
                throw ApiException::invalidPayload("Expected '{$key}' item '{$index}' to be an integer.");
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    private static function requireValue(array $payload, string $key): mixed
    {
        if (!\array_key_exists($key, $payload) || $payload[$key] === null) {
            throw ApiException::invalidPayload("Missing required field '{$key}'.");
        }

        return $payload[$key];
    }
}
