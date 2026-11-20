<?php

declare(strict_types=1);

namespace App\Modules\Ozon;

final readonly class OzonProduct
{
    public function __construct(
        public string $id,
        public ?string $model,
        public ?string $sku,
        public ?string $fboSku,
        public string $offer,
        public string $name,
        public int $price,
        public ?int $oldPrice,
        public int $stock,
        public bool $archived,
        public array $images,
        public string $description,
        public string $shortDescription,
        public ?string $gtin,
        public string $weight,
        public string $category,
        public array $warehouses,
    ) {}

    public static function fromOzon(array $detail, string $description, bool $listedArchived, array $warehouses = []): self
    {
        $id = self::positiveId($detail['id'] ?? null);
        $name = $detail['name'] ?? null;
        $offer = $detail['offer_id'] ?? null;
        $archived = $listedArchived || self::archived($detail);
        $price = self::price($detail['price'] ?? '', $archived || ($detail['visibility_details']['has_price'] ?? null) === false);
        $oldPrice = self::price($detail['old_price'] ?? '', true);
        if ($id === null || !is_string($name) || trim($name) === '' || !is_string($offer) || $offer === ''
            || (!$archived && ($detail['currency_code'] ?? null) !== 'RUB')) {
            throw new OzonException('Ozon вернул неполные данные карточки.');
        }

        $images = array_values(array_unique(array_filter(
            [...($detail['primary_image'] ?? []), ...($detail['images'] ?? [])],
            static fn ($url): bool => is_string($url) && strlen($url) <= 500
                && filter_var($url, FILTER_VALIDATE_URL) !== false && str_starts_with($url, 'https://'),
        )));
        $text = html_entity_decode(
            strip_tags(preg_replace('~<br\s*/?>|</(?:p|div|li|h[1-6])>~i', "\n", $description) ?? ''),
            ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE,
            'UTF-8',
        );
        $paragraphs = preg_split('/\R+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $paragraphs = array_values(array_filter(array_map('trim', $paragraphs), static fn (string $line) => $line !== ''));
        $html = implode('', array_map(
            static fn (string $line): string => '<p>' . htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>',
            $paragraphs,
        ));
        $stock = $archived ? 0 : self::stock($detail);
        $sku = self::positiveId($detail['sku'] ?? null);
        $fboSku = null;
        foreach ($detail['sources'] ?? [] as $row) {
            if (($row['source'] ?? null) === 'fbo') {
                $fboSku = self::positiveId($row['sku'] ?? null) ?? $fboSku;
            }
        }
        if ($fboSku === null) {
            foreach ($detail['stocks']['stocks'] ?? [] as $row) {
                if (($row['source'] ?? null) === 'fbo') {
                    $fboSku = self::positiveId($row['sku'] ?? null) ?? $fboSku;
                }
            }
        }

        preg_match('/\b\d+(?:[,.]\d+)?\s*(?:капсул\w*|таблет\w*|мл|л|кг|гр?)(?=\b|\.)/iu', $name, $size);
        $category = match (true) {
            (bool) preg_match('/капсул|таблет/iu', $name) => 'kapsuly',
            (bool) preg_match('/экстракт|концентрат|масло|клеточный сок|фракция/iu', $name) => 'ekstrakty-i-kontsentraty',
            default => 'produkciya',
        };
        $barcode = $detail['barcodes'][0] ?? null;

        return new self(
            $id, self::positiveId($detail['model_info']['model_id'] ?? null), $sku, $fboSku, $offer, mb_substr(trim($name), 0, 255),
            $price, $oldPrice > $price ? $oldPrice : null, $stock, $archived, $images, $html,
            mb_substr(implode(' ', $paragraphs), 0, 500),
            is_string($barcode) && preg_match('/^\d{8,14}$/D', $barcode) ? $barcode : null,
            $size[0] ?? '', $category, $archived ? [] : array_values($warehouses),
        );
    }

    public static function stock(array $detail, string $scheme = 'fbo'): int
    {
        $rows = $detail['stocks']['stocks'] ?? null;
        if (!is_array($rows) || !array_is_list($rows)) {
            throw new OzonException('Ozon не вернул остатки для одной из карточек.');
        }

        $available = 0;
        foreach ($rows as $row) {
            $source = $row['source'] ?? null;
            if (!is_string($source) || $source === '') {
                throw new OzonException('Ozon вернул остаток без указания схемы склада.');
            }
            if ($source !== $scheme) {
                continue;
            }
            $present = filter_var($row['present'] ?? null, FILTER_VALIDATE_INT);
            $reserved = filter_var($row['reserved'] ?? null, FILTER_VALIDATE_INT);
            if ($present === false || $reserved === false || $present < 0 || $reserved < 0) {
                throw new OzonException('Ozon вернул некорректный остаток товара.');
            }
            $available += max(0, $present - $reserved);
        }
        return $available;
    }

    public static function archived(array $detail): bool
    {
        return ($detail['is_archived'] ?? false) === true || ($detail['is_autoarchived'] ?? false) === true;
    }

    public static function positiveId(mixed $id): ?string
    {
        return (is_int($id) || is_string($id)) && preg_match('/^[1-9][0-9]{0,18}$/D', (string) $id)
            ? (string) $id
            : null;
    }

    private static function price(mixed $value, bool $optional): int
    {
        if ($optional && ($value === '' || $value === null)) {
            return 0;
        }
        if (!is_scalar($value) || !preg_match('/^\d{1,9}(?:\.\d{1,2})?$/D', (string) $value)) {
            throw new OzonException('Ozon вернул некорректную цену.');
        }
        // The storefront currently stores whole rubles.
        return (int) round((float) $value);
    }
}
