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

        return \is_array($payload['data']) ? $payload['data'] : [];
    }

    /**
     * @param array<array-key, mixed> $payload
     * @return list<array<array-key, mixed>>
     */
    public static function extractDataList(array $payload): array
    {
        $data = self::extractData($payload);

        /** @var list<array<array-key, mixed>> $items */
        $items = array_values(array_filter($data, \is_array(...)));

        if ($items === []) {
            return [];
        }

        return $items;
    }

    /**
     * @param array<array-key, mixed> $payload
     * @return list<string>
     */
    public static function extractStringList(array $payload): array
    {
        $data = self::extractData($payload);

        /** @var list<string> $items */
        $items = array_values(array_filter($data, \is_string(...)));

        if ($items === []) {
            return [];
        }

        return $items;
    }
}
