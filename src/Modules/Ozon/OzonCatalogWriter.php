<?php

declare(strict_types=1);

namespace App\Modules\Ozon;

use Doctrine\DBAL\Connection;

final readonly class OzonCatalogWriter
{
    public function __construct(private Connection $db) {}

    /**
     * Apply a complete, validated snapshot. Caller owns the transaction.
     *
     * @param array<int, OzonProduct> $products
     */
    public function apply(array $products, string $now): array
    {
        $result = ['added' => 0, 'updated' => 0, 'archived' => 0, 'visible' => 0, 'available' => 0, 'groups' => 0, 'total' => count($products)];
        $links = $this->db->fetchAllAssociativeIndexed('SELECT ozon_id, product_id, sku, fbo_sku, stock, archived FROM ozon_catalog_products');
        $categories = $this->db->fetchAllKeyValue('SELECT slug, id FROM categories WHERE deleted_at IS NULL');
        $groups = $this->groups($products, $now);
        $result['groups'] = count($groups);
        foreach ($products as $product) {
            $row = $this->productRow($product, $links[$product->id]['product_id'] ?? null);
            if ($product->archived && $row === false) {
                continue;
            }

            $fields = $this->visibility($product->stock, $product->price, $product->archived, $row, $now);
            if (!$product->archived) {
                $fields += $this->cardFields($product);
            }
            $groupId = !$product->archived && $product->model !== null ? $groups[$product->model] : null;
            $changed = false;
            if ($row === false) {
                $categoryId = $categories[$product->category] ?? $categories['produkciya'] ?? null;
                if ($categoryId === null) {
                    throw new OzonException('Для новых товаров Ozon отсутствует категория каталога.');
                }
                $this->db->insert('products', $fields + [
                    'slug' => 'ozon-' . $product->id,
                    'category_id' => (string) $categoryId,
                    'created_at' => $now,
                    'image' => $product->images[0] ?? '',
                    'images' => json_encode($product->images, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
                    'image_alt' => $product->name,
                ]);
                $localId = (int) $this->db->lastInsertId();
                $this->insertImages($localId, $product, $now);
                $this->saveMembership($localId, $groupId, $now);
                ++$result['added'];
            } else {
                $localId = (int) $row['id'];
                $changed = $this->update('products', $localId, $row, $fields, $now);
                $changed = $this->saveMembership($localId, $groupId, $now) || $changed;
            }

            $mapping = [
                'sku' => $product->sku,
                'fbo_sku' => $product->fboSku,
                'stock' => $product->stock,
                'archived' => (int) $product->archived,
            ];
            if (!isset($links[$product->id])) {
                $this->db->insert('ozon_catalog_products', $mapping + ['ozon_id' => $product->id, 'product_id' => $localId]);
            } elseif ($this->differs($links[$product->id], $mapping)) {
                $this->db->update('ozon_catalog_products', $mapping, ['ozon_id' => $product->id]);
                $changed = true;
            }
            if ($row !== false && $product->archived && $row['deleted_at'] === null) {
                ++$result['archived'];
            } elseif ($changed) {
                ++$result['updated'];
            }
            if ($fields['is_active'] === 1) {
                ++$result['visible'];
                if ($fields['availability'] === 'in_stock') {
                    ++$result['available'];
                }
            }
        }

        foreach (array_diff_key($links, $products) as $id => $link) {
            if ((int) $link['archived'] === 0) {
                $this->db->update('ozon_catalog_products', ['archived' => 1, 'stock' => 0], ['ozon_id' => $id]);
                $result['archived'] += $this->archiveProduct((int) $link['product_id'], $now);
                $this->saveMembership((int) $link['product_id'], null, $now);
            }
        }
        $result['archived'] += $this->db->executeStatement(
            "UPDATE products SET is_active = 0, availability = 'out_of_stock', deleted_at = ?, updated_at = ?
             WHERE deleted_at IS NULL AND id NOT IN (SELECT product_id FROM ozon_catalog_products)",
            [$now, $now],
        );
        $this->archiveEmptyGroups($now);

        return $result;
    }

    /** Apply a fresh stock reading to an already imported, non-archived product. */
    public function updateStock(string $id, array $detail, string $now): bool
    {
        $link = $this->db->fetchAssociative(
            'SELECT l.archived, l.stock, p.id, p.price, p.deleted_at, p.availability FROM ozon_catalog_products l
             JOIN products p ON p.id = l.product_id WHERE l.ozon_id = ? FOR UPDATE',
            [$id],
        );
        if ($link === false || (int) $link['archived'] === 1) {
            return false;
        }

        $stock = OzonProduct::archived($detail) ? 0 : OzonProduct::stock($detail);
        if ((int) $link['stock'] !== $stock) {
            $this->db->update('ozon_catalog_products', ['stock' => $stock], ['ozon_id' => $id]);
        }
        if ($link['deleted_at'] !== null) {
            return false;
        }
        $availability = $stock > 0 && (int) $link['price'] > 0 ? 'in_stock' : 'out_of_stock';

        return $this->update('products', (int) $link['id'], $link, ['availability' => $availability], $now);
    }

    private function productRow(OzonProduct $product, mixed $localId): array|false
    {
        if ($localId !== null) {
            $row = $this->db->fetchAssociative('SELECT * FROM products WHERE id = ? FOR UPDATE', [$localId]);
            if ($row === false) {
                throw new OzonException('Отсутствует локальная карточка, связанная с Ozon.');
            }
            return $row;
        }
        // Reuse existing Ozon cards and their attachments on the first tracked import.
        return $this->db->fetchAssociative(
            "SELECT * FROM products WHERE slug = ? AND ozon_link LIKE 'https://www.ozon.ru/product/%' FOR UPDATE",
            ['ozon-' . $product->id],
        );
    }

    private function cardFields(OzonProduct $product): array
    {
        $externalSku = $product->sku ?? $product->fboSku ?? $product->id;
        return [
            'name' => $product->name,
            'h1' => $product->name,
            'price' => $product->price,
            'old_price' => $product->oldPrice,
            'weight' => $product->weight,
            'description' => $product->description,
            'short_description' => $product->shortDescription,
            'sku' => mb_substr($product->offer, 0, 100),
            'gtin' => $product->gtin,
            'ozon_link' => 'https://www.ozon.ru/product/' . $externalSku . '/',
        ];
    }

    private function visibility(int $stock, int $price, bool $archived, array|false $row, string $now): array
    {
        $published = !$archived && ($row === false || $row['deleted_at'] !== null || (bool) $row['is_active']);
        return [
            'is_active' => (int) $published,
            'availability' => !$archived && $stock > 0 && $price > 0 ? 'in_stock' : 'out_of_stock',
            'deleted_at' => $archived ? ($row['deleted_at'] ?? $now) : null,
            'published_at' => $row['published_at'] ?? ($published ? $now : null),
        ];
    }

    private function groups(array $products, string $now): array
    {
        $existing = $this->db->fetchAllKeyValue('SELECT ozon_model_id, group_id FROM ozon_catalog_groups');
        $legacy = $this->db->fetchAllKeyValue(
            "SELECT p.slug, i.group_id FROM product_group_items i JOIN products p ON p.id = i.product_id
             WHERE p.slug LIKE 'ozon-%' AND p.ozon_link LIKE 'https://www.ozon.ru/product/%'",
        );
        $assigned = array_fill_keys(array_values($existing), true);
        $groups = [];
        ksort($products);
        foreach ($products as $product) {
            $model = $product->model;
            if ($product->archived || $model === null || isset($groups[$model])) {
                continue;
            }
            $groupId = $existing[$model] ?? null;
            $previous = $legacy['ozon-' . $product->id] ?? null;
            if ($groupId === null && $previous !== null && !isset($assigned[$previous])) {
                $groupId = $previous;
            }
            if ($groupId === null) {
                $this->db->insert('product_groups', ['name' => $product->name, 'created_at' => $now]);
                $groupId = (int) $this->db->lastInsertId();
            } else {
                $row = $this->db->fetchAssociative('SELECT * FROM product_groups WHERE id = ? FOR UPDATE', [$groupId]);
                if ($row === false) {
                    throw new OzonException('Отсутствует локальная группа, связанная с Ozon.');
                }
                $this->update('product_groups', (int) $groupId, $row, ['name' => $product->name, 'deleted_at' => null], $now);
            }
            if (!isset($existing[$model])) {
                $this->db->insert('ozon_catalog_groups', ['ozon_model_id' => $model, 'group_id' => $groupId]);
            }
            $assigned[$groupId] = true;
            $groups[$model] = (int) $groupId;
        }
        return $groups;
    }

    private function archiveProduct(int $id, string $now): int
    {
        return $this->db->executeStatement(
            "UPDATE products SET deleted_at = ?, updated_at = ?, is_active = 0, availability = 'out_of_stock'
             WHERE id = ? AND deleted_at IS NULL",
            [$now, $now, $id],
        );
    }

    private function archiveEmptyGroups(string $now): void
    {
        $this->db->executeStatement(
            "UPDATE product_groups SET deleted_at = ?, updated_at = ? WHERE deleted_at IS NULL
             AND id NOT IN (SELECT i.group_id FROM product_group_items i JOIN products p ON p.id = i.product_id WHERE p.deleted_at IS NULL)",
            [$now, $now],
        );
    }

    private function insertImages(int $id, OzonProduct $product, string $now): void
    {
        foreach ($product->images as $index => $path) {
            $this->db->insert('product_images', [
                'product_id' => $id, 'path' => $path, 'alt' => $product->name, 'title' => $product->name,
                'sort_order' => $index, 'is_main' => (int) ($index === 0), 'created_at' => $now,
            ]);
        }
    }

    private function saveMembership(int $id, ?int $groupId, string $now): bool
    {
        $current = $this->db->fetchOne('SELECT group_id FROM product_group_items WHERE product_id = ?', [$id]);
        if (($current === false ? null : (int) $current) === $groupId) {
            return false;
        }
        if ($groupId === null) {
            $this->db->delete('product_group_items', ['product_id' => $id]);
        } elseif ($current === false) {
            $this->db->insert('product_group_items', ['product_id' => $id, 'group_id' => $groupId, 'created_at' => $now]);
        } else {
            $this->db->update('product_group_items', ['group_id' => $groupId, 'updated_at' => $now], ['product_id' => $id]);
        }
        return true;
    }

    private function update(string $table, int $id, array $current, array $fields, string $now): bool
    {
        if (!$this->differs($current, $fields)) {
            return false;
        }
        $this->db->update($table, $fields + ['updated_at' => $now], ['id' => $id]);
        return true;
    }

    private function differs(array $row, array $fields): bool
    {
        foreach ($fields as $field => $value) {
            $current = $row[$field] ?? null;
            if ($value === null ? $current !== null : ($current === null || (string) $current !== (string) $value)) {
                return true;
            }
        }
        return false;
    }
}
