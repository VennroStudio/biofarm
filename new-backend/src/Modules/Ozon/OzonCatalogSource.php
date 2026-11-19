<?php

declare(strict_types=1);

namespace App\Modules\Ozon;

final readonly class OzonCatalogSource
{
    public function __construct(private OzonApi $api) {}

    /**
     * @return array<int, OzonProduct> Complete catalog, indexed by Ozon product ID.
     */
    public function catalog(string $token, array $previouslyListed): array
    {
        $list = $this->listing($token);
        $missing = array_diff($previouslyListed, array_keys($list));
        if ($list === [] || count($missing) > max(5, floor(count($previouslyListed) * 0.30))) {
            throw new OzonException('Выгрузка Ozon пуста или резко сократилась. Каталог сохранён.');
        }

        // Confirm disappearance independently. An active card omitted by the list is an incomplete snapshot.
        foreach ($this->details($token, $missing, allowMissing: true) as $row) {
            if (!OzonProduct::archived($row)) {
                throw new OzonException('Ozon вернул неполный список товаров. Каталог сохранён.');
            }
        }

        $products = [];
        foreach ($this->details($token, array_keys($list)) as $id => $detail) {
            $archived = $list[$id];
            $description = '';
            if (!$archived && !OzonProduct::archived($detail)) {
                $reply = $this->api->seller('/v1/product/info/description', $token, ['product_id' => $id]);
                $result = $reply['result'] ?? null;
                if (OzonProduct::positiveId($result['id'] ?? null) !== (string) $id
                    || !is_string($result['description'] ?? null)) {
                    throw new OzonException('Ozon не вернул описание для одной из карточек.');
                }
                $description = $result['description'];
            }
            $products[$id] = OzonProduct::fromOzon($detail, $description, $archived);
        }
        return $products;
    }

    /** @return array<int, array> */
    public function details(string $token, array $ids, bool $allowMissing = false): array
    {
        $rows = [];
        foreach (array_chunk(array_values($ids), 100) as $chunk) {
            $requested = array_fill_keys($chunk, true);
            $reply = $this->api->seller('/v3/product/info/list', $token, [
                'product_id' => array_map(strval(...), $chunk),
            ]);
            $items = $reply['items'] ?? null;
            if (!is_array($items) || !array_is_list($items)) {
                throw new OzonException('Ozon вернул некорректные сведения о товарах.');
            }
            foreach ($items as $row) {
                $id = OzonProduct::positiveId($row['id'] ?? null);
                if ($id === null || !isset($requested[$id])) {
                    throw new OzonException('Список карточек Ozon не совпадает с запросом.');
                }
                unset($requested[$id]);
                $rows[$id] = $row;
            }
            if ($requested !== [] && !$allowMissing) {
                throw new OzonException('Ozon вернул данные только для части товаров.');
            }
        }
        return $rows;
    }

    private function listing(string $token): array
    {
        $cursor = '';
        $seenCursors = [];
        $list = [];
        $total = null;
        do {
            $reply = $this->api->seller('/v3/product/list', $token, [
                'filter' => ['visibility' => 'ALL'],
                'last_id' => $cursor,
                'limit' => 1000,
            ]);
            $page = $reply['result'] ?? [];
            $items = $page['items'] ?? null;
            $next = $page['last_id'] ?? '';
            if (!is_array($items) || !array_is_list($items) || !is_string($next)) {
                throw new OzonException('Ozon вернул некорректную страницу каталога.');
            }
            if (isset($page['total'])) {
                $pageTotal = filter_var($page['total'], FILTER_VALIDATE_INT);
                if ($pageTotal === false || $pageTotal < 0 || ($total !== null && $pageTotal !== $total)) {
                    throw new OzonException('Количество товаров изменилось во время выгрузки Ozon.');
                }
                $total = $pageTotal;
            }
            foreach ($items as $row) {
                $id = OzonProduct::positiveId($row['product_id'] ?? null);
                if ($id === null || isset($list[$id]) || !is_bool($row['archived'] ?? null)) {
                    throw new OzonException('Ozon вернул повторяющиеся или неполные записи каталога.');
                }
                $list[$id] = $row['archived'];
            }
            if ($next === '' || $items === [] || ($total !== null && count($list) === $total)) {
                break;
            }
            if (isset($seenCursors[$next]) || count($seenCursors) >= 1000) {
                throw new OzonException('Не удалось полностью прочитать каталог Ozon.');
            }
            $seenCursors[$next] = true;
            $cursor = $next;
        } while (true);
        if ($total !== null && count($list) !== $total) {
            throw new OzonException('Выгружены не все страницы каталога Ozon.');
        }
        return $list;
    }
}
