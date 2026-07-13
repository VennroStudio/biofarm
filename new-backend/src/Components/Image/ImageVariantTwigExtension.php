<?php

declare(strict_types=1);

namespace App\Components\Image;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ImageVariantTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly ImageVariantLocator $locator,
    ) {}

    /**
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('optimized_image', $this->optimizedImage(...), ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param array<string, bool|int|string|null> $attributes
     */
    public function optimizedImage(string $src, string $alt = '', array $attributes = []): string
    {
        $attributes = [
            ...$attributes,
            'src' => $src,
            'alt' => $alt,
        ];

        $image = '<img' . $this->attributes($attributes) . '>';
        $sources = $this->locator->sources($src);

        if ($sources === []) {
            return $image;
        }

        $sourceHtml = '';
        foreach ($sources as $source) {
            if ($source['src'] === $src) {
                continue;
            }

            $sourceHtml .= '<source srcset="' . $this->escape($source['src']) . '" type="' . $this->escape($source['type']) . '">';
        }

        if ($sourceHtml === '') {
            return $image;
        }

        return '<picture>' . $sourceHtml . $image . '</picture>';
    }

    /**
     * @param array<string, bool|int|string|null> $attributes
     */
    private function attributes(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            $name = $this->escape($name);
            if ($value === true) {
                $html .= ' ' . $name;
                continue;
            }

            $html .= ' ' . $name . '="' . $this->escape((string)$value) . '"';
        }

        return $html;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
