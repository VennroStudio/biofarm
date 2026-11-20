<?php

declare(strict_types=1);

namespace App\Modules\Ozon;

use App\Modules\Program\Service\ProgramMath;
use DomainException;

final readonly class OzonDelivery
{
    public function __construct(
        private OzonApi $api,
        private OzonConnection $connection,
    ) {}

    private function request(string $path, array $payload): array
    {
        try {
            return $this->api->seller($path, $this->connection->token(), $payload);
        } catch (OzonException $exception) {
            throw new OzonException($exception->getMessage(), $exception->getCode(), $exception,
                'Не удалось проверить доставку Ozon. Попробуйте ещё раз.');
        }
    }

    public function buyerAllowed(string $phone): bool
    {
        if (!preg_match('/^[0-9]{10,15}$/D', $phone)) {
            throw new OzonException('Укажите телефон с кодом страны.');
        }
        $possible = $this->request('/v1/delivery/check', ['client_phone' => $phone])['is_possible'] ?? null;
        if (!is_bool($possible)) {
            throw new OzonException('Ozon не подтвердил проверку номера покупателя.');
        }
        return $possible;
    }

    public function pickupPoint(int $id): array
    {
        return $this->pickupPointDetails([$id])[$id] ?? throw new OzonException('Пункт выдачи Ozon недоступен.');
    }

    public function pickupPointIds(): \Generator
    {
        $cursor = '';
        $seen = [];
        do {
            $reply = $this->request('/v1/delivery/point/list', $cursor === '' ? [] : ['cursor' => $cursor]);
            $points = $reply['points'] ?? null;
            if (!is_array($points) || !array_is_list($points)) {
                throw new OzonException('Ozon не вернул справочник пунктов выдачи.');
            }
            foreach ($points as $point) {
                $id = OzonProduct::positiveId($point['map_point_id'] ?? null);
                if ($id === null || strlen($id) > 15) {
                    throw new OzonException('Ozon вернул некорректный идентификатор пункта выдачи.');
                }
                yield (int)$id;
            }
            $cursor = (string)($reply['cursor'] ?? '');
            if ($cursor !== '' && (isset($seen[$cursor]) || $points === [])) {
                throw new OzonException('Ozon вернул неполный справочник пунктов выдачи.');
            }
            $seen[$cursor] = true;
        } while ($cursor !== '');
    }

    public function pickupPointDetails(array $ids): array
    {
        $response = $this->request('/v1/delivery/point/info', ['map_point_ids' => $ids]);
        $details = [];
        foreach ($response['points'] ?? [] as $point) {
            $method = $point['delivery_method'] ?? [];
            $id = (int) ($method['map_point_id'] ?? 0);
            if (($point['enabled'] ?? false) === true && (int)($method['delivery_type']['id'] ?? 0) === 1002
                && in_array($id, $ids, true) && trim($method['address'] ?? '') !== '') {
                $details[$id] = $method;
            }
        }
        return $details;
    }

    public function quote(string $phone, array $delivery, array $items, string $schema = 'FBO', array $aliases = []): array
    {
        $result = $this->request('/v2/delivery/checkout', [
            'buyer_phone' => $phone,
            'delivery_schema' => $schema,
            'delivery_type' => $delivery,
            'items' => $items,
        ]);
        $splits = $result['splits'] ?? null;
        if (!is_array($splits) || !array_is_list($splits)) {
            throw new OzonException('Ozon вернул неполный расчёт доставки.');
        }
        if ($splits === []) {
            throw new OzonException('Ozon не нашёл способ доставки для этой корзины.', quoteUnavailable: true);
        }
        foreach ($splits as $split) {
            $method = $split['delivery_method'] ?? [];
            if (($split['unavailable_reason'] ?? 'UNSPECIFIED') !== 'UNSPECIFIED'
                || ($method['unavailable_reason'] ?? 'UNSPECIFIED') !== 'UNSPECIFIED') {
                throw new OzonException('Ozon не может доставить всю корзину по этой схеме.', quoteUnavailable: true);
            }
            if (!in_array($split['delivery_schema'] ?? null, $schema === 'MIX' ? ['FBO', 'FBS'] : [$schema], true)
                || empty($split['items']) || empty($method['timeslots'])) {
                throw new OzonException('Ozon вернул неполный расчёт доставки.');
            }
        }
        foreach ($splits as $split) {
            $slot = $split['delivery_method']['timeslots'][0] ?? [];
            $range = $slot['logistic_date_range'] ?? [];
            if (!OzonProduct::positiveId($split['warehouse_id'] ?? null) || empty($split['delivery_method']['id'])
                || empty($slot['timeslot_id']) || empty($range['from']) || empty($range['to'])) {
                throw new OzonException('Ozon вернул неполные параметры отправления.');
            }
        }
        if ($this->quantities($items, $aliases) !== $this->quantities(array_merge(...array_column($splits, 'items')), $aliases)) {
            throw new OzonException('Ozon изменил состав корзины. Проверьте количество товаров.');
        }
        return $splits;
    }

    public function quotedCostMinor(array $splits): int
    {
        $total = 0;
        foreach ($splits as $split) {
            $commission = $split['commissions']['total'] ?? [];
            if (($commission['currency'] ?? null) !== 'RUB') {
                throw new OzonException('Ozon не указал стоимость доставки в рублях. Повторите проверку.');
            }
            try {
                $total += ProgramMath::minor($commission['amount'] ?? null);
            } catch (DomainException $exception) {
                throw new OzonException('Ozon не указал стоимость доставки. Повторите проверку.', previous: $exception);
            }
        }
        return $total;
    }

    /** Stable customer commitment. Ozon can rotate slot IDs between quote and payment. */
    public function shipmentPlan(array $splits): array
    {
        $parts = [];
        foreach ($splits as $split) {
            $range = $split['delivery_method']['timeslots'][0]['logistic_date_range'];
            $parts[] = ['schema' => $split['delivery_schema'], 'warehouse' => (string)$split['warehouse_id'],
                'items' => $this->quantities($split['items']), 'from' => $range['from'], 'to' => $range['to'],
                'costMinor' => $this->quotedCostMinor([$split])];
        }
        sort($parts);
        return $parts;
    }

    public static function dates(array $splits): array
    {
        $dates = array_map(static fn (array $split): string => $split['delivery_method']['timeslots'][0]['logistic_date_range']['from'], $splits);
        sort($dates);
        return $dates;
    }

    /** Call after the payment has been confirmed and use the freshly verified quote. */
    public function create(array $buyer, array $recipient, array $delivery, array $splits, array $priceBySku, string $schema = 'FBO'): array
    {
        $shipments = [];
        foreach ($splits as $split) {
            $method = $split['delivery_method'];
            $slot = $method['timeslots'][0];
            $items = [];
            foreach ($split['items'] as $item) {
                $sku = (int) $item['sku'];
                $price = $priceBySku[$sku] ?? null;
                if (!is_int($price) || $price < 0) {
                    throw new OzonException('Для товара Ozon отсутствует подтверждённая цена.');
                }
                $items[] = [
                    'sku' => $sku,
                    'quantity' => (int) $item['quantity'],
                    'price' => ['currency_code' => 'RUB', 'units' => $price, 'nanos' => 0],
                ];
            }
            $shipments[] = [
                'warehouse_id' => $split['warehouse_id'],
                'delivery_method' => [
                    'delivery_method_id' => $method['id'],
                    'delivery_type' => $method['delivery_type'],
                    'timeslot_id' => $slot['timeslot_id'],
                    'logistic_date_range' => $slot['logistic_date_range'],
                ],
                'items' => $items,
            ];
        }

        $reply = $this->request('/v2/order/create', [
            'buyer' => $buyer,
            'recipient' => $recipient,
            'delivery' => $delivery,
            'delivery_schema' => $schema,
            'splits' => $shipments,
        ]);
        if (!is_string($reply['order_number'] ?? null) || $reply['order_number'] === '') {
            throw new OzonException('Ozon не подтвердил создание заказа.');
        }
        return $reply;
    }

    public function postings(string $orderNumber, string $schema = 'FBO', ?string $placedAt = null): array
    {
        if ($schema === 'MIX') {
            return [...$this->postings($orderNumber, 'FBO'), ...$this->postings($orderNumber, 'FBS', $placedAt)];
        }
        $filter = ['order_numbers' => [$orderNumber]];
        if ($schema === 'FBS') {
            $placed = $placedAt ? strtotime($placedAt) : time();
            $filter += ['since' => gmdate(DATE_ATOM, $placed - 86400), 'to' => gmdate(DATE_ATOM, min(time(), $placed + 360 * 86400))];
        }
        $postings = [];
        $cursor = '';
        $seen = [];
        do {
            $reply = $this->request($schema === 'FBS' ? '/v4/posting/fbs/list' : '/v3/posting/fbo/list', [
                'filter' => $filter,
                'limit' => 100,
                'cursor' => $cursor,
            ]);
            $rows = $reply['result'] ?? $reply['postings'] ?? null;
            if (!is_array($rows) || !array_is_list($rows)) {
                throw new OzonException('Ozon не вернул список отправлений.');
            }
            foreach ($rows as $row) {
                $row['schema'] = $schema;
                $postings[] = $row;
            }
            $cursor = (string) ($reply['cursor'] ?? '');
            if (!empty($reply['has_next']) && ($cursor === '' || $rows === [])) {
                throw new OzonException('Ozon вернул неполную страницу отправлений.');
            }
            if ($cursor !== '' && isset($seen[$cursor])) {
                throw new OzonException('Ozon вернул повторяющуюся страницу отправлений.');
            }
            $seen[$cursor] = true;
        } while (($reply['has_next'] ?? ($cursor !== '')) && $rows !== []);
        return $postings;
    }

    public function receivedExemplars(string $posting): array
    {
        $reply = $this->request('/v1/posting/marks', ['posting_numbers' => [$posting]]);
        if (!empty($reply['invalid_postings']) || !is_array($reply['issued_exemplars'] ?? null)) {
            throw new OzonException('Ozon пока не вернул выданные экземпляры и коды маркировки.');
        }
        $issued = $unissued = $seen = [];
        foreach (['issued_exemplars', 'non_issued_exemplars'] as $key) {
            foreach ($reply[$key] ?? [] as $unit) {
                $sku = OzonProduct::positiveId($unit['sku'] ?? null);
                $id = OzonProduct::positiveId($unit['exemplar_id'] ?? null);
                if ($sku === null || $id === null || isset($seen[$id]) || ($unit['posting_number'] ?? '') !== $posting) {
                    throw new OzonException('Ozon вернул некорректные данные об экземплярах.');
                }
                $seen[$id] = true;
                if ($key === 'non_issued_exemplars') {
                    $unissued[$sku] = ($unissued[$sku] ?? 0) + 1;
                    continue;
                }
                $codes = $unit['mandatory_marks'] ?? [];
                if (!is_array($codes) || count($codes) > 1 || ($codes !== [] && (!is_string($codes[0] ?? null) || trim($codes[0]) === ''))) {
                    throw new OzonException('Для экземпляра Ozon получена неоднозначная маркировка.');
                }
                $issued[$sku][] = $codes[0] ?? null;
            }
        }
        return [$issued, $unissued];
    }

    public function fbsExemplars(string $number, array $returned, array $refusedIds, array $confirmedIds = []): array
    {
        $reply = $this->request('/v3/posting/fbs/get', ['posting_number' => $number, 'with' => ['product_exemplars' => true]]);
        $posting = $reply['result'] ?? [];
        if (($posting['posting_number'] ?? '') !== $number || !is_array($posting['product_exemplars']['products'] ?? null)) {
            throw new OzonException('Ozon пока не вернул экземпляры отправления FBS.');
        }
        $issued = $unissued = $seen = [];
        foreach ($posting['product_exemplars']['products'] as $product) {
            $sku = OzonProduct::positiveId($product['sku'] ?? null);
            if (!$sku || !is_array($product['exemplars'] ?? null)) {
                throw new OzonException('Неполные данные экземпляров FBS.');
            }
            foreach ($product['exemplars'] as $unit) {
                $id = OzonProduct::positiveId($unit['exemplar_id'] ?? null);
                if (!$id || isset($seen[$id])) {
                    throw new OzonException('Не удалось сопоставить экземпляр FBS.');
                }
                $seen[$id] = true;
                $official = in_array($id, $refusedIds[$sku] ?? [], true);
                if ($confirmedIds && $official && !in_array($id, $confirmedIds, true)) {
                    throw new OzonException('Экземпляр из сверки противоречит данным возврата Ozon.');
                }
                if ($official || in_array($id, $confirmedIds, true)) {
                    $unissued[$sku] = ($unissued[$sku] ?? 0) + 1;
                } else {
                    $mark = $unit['mandatory_mark'] ?? null;
                    $issued[$sku][] = is_string($mark) && trim($mark) !== '' ? $mark : null;
                }
            }
            if (($unissued[$sku] ?? 0) !== ($returned[$sku] ?? 0)) {
                throw new OzonException('Требуется сверка невыкупленных экземпляров FBS в Ozon Seller.');
            }
        }
        if ($confirmedIds && (array_diff($confirmedIds, array_keys($seen)) || count($confirmedIds) !== array_sum($returned))) {
            throw new OzonException('ID экземпляров должны совпадать с составом отправления и количеством невыкупа Ozon.');
        }
        return [$issued, $unissued];
    }

    public function partialReturns(string $orderNumber, array $postings, array &$refusedIds = []): array
    {
        $last = 0;
        $quantities = $seen = [];
        do {
            $result = $this->request('/v1/returns/list', ['filter' => ['posting_numbers' => $postings], 'limit' => 100, 'last_id' => $last]);
            if (!is_array($result['returns'] ?? null)) {
                throw new OzonException('Ozon не вернул список возвратов.');
            }
            $next = $last;
            foreach ($result['returns'] as $return) {
                $returnId = OzonProduct::positiveId($return['id'] ?? null);
                if ($returnId === null || isset($seen[$returnId])) {
                    throw new OzonException('Ozon вернул неполную или повторную запись возврата.');
                }
                $seen[$returnId] = true;
                $next = max($next, (int)$returnId);
                if (($return['type'] ?? null) !== 'PartialReturn' || ($return['order_number'] ?? '') !== $orderNumber
                    || !in_array($return['posting_number'] ?? '', $postings, true)) {
                    continue;
                }
                $sku = OzonProduct::positiveId($return['product']['sku'] ?? null);
                $quantity = filter_var($return['product']['quantity'] ?? null, FILTER_VALIDATE_INT);
                if ($sku === null || $quantity === false || $quantity < 1) {
                    throw new OzonException('Ozon вернул неполные данные о невыкупе.');
                }
                $point = $return['posting_number'];
                foreach ($return['exemplars'] ?? [] as $exemplar) {
                    if (($id = OzonProduct::positiveId($exemplar['id'] ?? null)) !== null) {
                        $refusedIds[$point][$sku][] = $id;
                    }
                }
                $quantities[$point][$sku] = ($quantities[$point][$sku] ?? 0) + $quantity;
            }
            if (!empty($result['has_next']) && $next <= $last) {
                throw new OzonException('Ozon вернул повторяющуюся страницу возвратов.');
            }
            $last = $next;
        } while (!empty($result['has_next']));
        return $quantities;
    }

    public function cancellationReason(string $orderNumber): ?int
    {
        $order = ['order_number' => $orderNumber];
        $allowed = $this->request('/v1/order/cancel/check', $order)['cancellable'] ?? null;
        if (!is_bool($allowed)) {
            throw new OzonException('Ozon не подтвердил возможность отмены.');
        }
        if (!$allowed) {
            return null;
        }
        $reasons = array_column($this->request('/v1/cancel-reason/list-by-order', $order)['reasons'] ?? [], 'id');
        $reason = in_array(508, array_map('intval', $reasons), true) ? 508 : ($reasons[0] ?? null);
        if (!is_numeric($reason)) {
            throw new OzonException('Ozon не вернул причину отмены заказа.');
        }
        return (int)$reason;
    }

    public function requestCancellation(string $orderNumber, int $reason): void
    {
        $this->request('/v1/order/cancel', ['order_number' => $orderNumber, 'reason_id' => $reason]);
    }

    public function cancellationStatus(string $orderNumber): array
    {
        return $this->request('/v1/order/cancel/status', ['order_number' => $orderNumber]);
    }

    public function cancel(string $orderNumber): array
    {
        $reason = $this->cancellationReason($orderNumber);
        if ($reason === null) {
            throw new OzonException('Ozon пока не разрешает отменить заказ.');
        }
        $this->requestCancellation($orderNumber, $reason);
        return $this->cancellationStatus($orderNumber);
    }

    private function quantities(array $items, array $aliases = []): array
    {
        $quantities = [];
        foreach ($items as $item) {
            $sku = OzonProduct::positiveId($item['sku'] ?? null);
            $count = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
            if (!$sku || $count === false || $count <= 0) {
                throw new OzonException('Ozon вернул ошибочный состав отправления.');
            }
            if ($aliases !== [] && !isset($aliases[$sku])) {
                throw new OzonException('Ozon вернул незнакомый SKU.');
            }
            $sku = $aliases[$sku] ?? $sku;
            $quantities[$sku] = ($quantities[$sku] ?? 0) + $count;
        }
        ksort($quantities);
        return $quantities;
    }
}
