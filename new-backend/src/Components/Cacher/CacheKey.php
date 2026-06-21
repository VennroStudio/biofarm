<?php

declare(strict_types=1);

namespace App\Components\Cacher;

final readonly class CacheKey
{
    /**
     * @param list<int|string> $parts
     */
    public static function tag(string $prefix, array $parts): string
    {
        if ($parts === []) {
            return $prefix;
        }

        return $prefix . '_' . implode('_', $parts);
    }

    public static function byClass(string $tag, string $className): string
    {
        return $tag . '.' . self::shortClassName($className);
    }

    private static function shortClassName(string $className): string
    {
        $position = strrpos($className, '\\');

        return $position === false ? $className : substr($className, $position + 1);
    }
}
