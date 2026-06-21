<?php

declare(strict_types=1);

namespace App\Components\Frontend;

final readonly class FrontendUrlGenerator
{
    /**
     * @param array<string, string> $baseUrls Ключи — значения из FrontendUrlType::value
     */
    public function __construct(
        private array $baseUrls,
    ) {}

    public function generate(string $uri = '', array $params = [], FrontendUrlType $type = FrontendUrlType::MAIN): string
    {
        $baseUrl = $this->getBaseUrl($type);

        return $baseUrl
            . ($uri !== '' ? '/' . $uri : '')
            . ($params !== [] ? '?' . http_build_query($params) : '');
    }

    public function getBaseUrl(FrontendUrlType $type = FrontendUrlType::MAIN): string
    {
        return $this->baseUrls[$type->value];
    }
}
