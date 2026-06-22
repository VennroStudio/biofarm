<?php

declare(strict_types=1);

namespace App\Components\Twig;

use DateTimeImmutable;
use Exception;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class FormattingExtension extends AbstractExtension
{
    /**
     * @return list<TwigFilter>
     */
    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('money', $this->money(...)),
            new TwigFilter('rating', $this->rating(...)),
            new TwigFilter('short_date', $this->shortDate(...)),
        ];
    }

    public function money(float|int|string $amount, string $currency = '$'): string
    {
        return $currency . number_format((float)$amount, 2, '.', ' ');
    }

    public function rating(float|int|string $rating, int $precision = 1): string
    {
        return number_format((float)$rating, $precision, '.', ' ') . '/5';
    }

    public function shortDate(string $date): string
    {
        try {
            return new DateTimeImmutable($date)->format('Y-m-d');
        } catch (Exception) {
            return $date;
        }
    }
}
