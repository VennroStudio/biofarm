<?php

declare(strict_types=1);

namespace App\Components\Integration\Bitrix24;

final readonly class Bitrix24WebhookValidator
{
    public function valid(string $url): bool
    {
        $parts = parse_url($url);
        if (!\is_array($parts)) {
            return false;
        }

        $scheme = (string)($parts['scheme'] ?? '');
        $host = (string)($parts['host'] ?? '');
        $path = (string)($parts['path'] ?? '');

        return $scheme === 'https'
            && str_contains($host, 'bitrix24.')
            && preg_match('~^/rest/\d+/[^/]+/?$~', $path) === 1;
    }
}
