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
        return ($this->request('/v1/delivery/check', ['client_phone' => $phone])['is_possible'] ?? false) === true;
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

    public function quote(string $phone, array $delivery, array $items): array
    {
        $result = $this->request('/v2/delivery/checkout', [
            'buyer_phone' => $phone,
            'delivery_schema' => 'FBO',
            'delivery_type' => $delivery,
            'items' => $items,
        ]);
        $splits = $result['splits'] ?? [];
        if ($splits === []) {
            throw new OzonException('Ozon не нашёл способ доставки для этой корзины.', quoteUnavailable: true);
        }
        foreach ($splits as $split) {
            $method = $split['delivery_method'] ?? [];
            if (($split['delivery_schema'] ?? null) !== 'FBO'
                || ($split['unavailable_reason'] ?? 'UNSPECIFIED') !== 'UNSPECIFIED'
                || ($method['unavailable_reason'] ?? 'UNSPECIFIED') !== 'UNSPECIFIED'
                || empty($split['items']) || empty($method['timeslots'])) {
                throw new OzonException('Ozon не может доставить все товары этой корзины.', quoteUnavailable: true);
            }
        }
        if ($this->quantities($items) !== $this->quantities(array_merge(...array_column($splits, 'items')))) {
            throw new OzonException('Ozon изменил состав корзины. Проверьте количество товаров.', quoteUnavailable: true);
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

    public static function dates(array $splits): array
    {
        $dates = array_map(static fn (array $split): string => $split['delivery_method']['timeslots'][0]['logistic_date_range']['from'], $splits);
        sort($dates);
        return $dates;
    }

    /** Call after the payment has been confirmed and use the freshly verified quote. */
    public function create(array $buyer, array $recipient, array $delivery, array $splits, array $priceBySku): array
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
            'delivery_schema' => 'FBO',
            'splits' => $shipments,
        ]);
        if (!is_string($reply['order_number'] ?? null) || $reply['order_number'] === '') {
            throw new OzonException('Ozon не подтвердил создание заказа.');
        }
        return $reply;
    }

    public function postings(string $orderNumber): array
    {
        $postings = [];
        $cursor = '';
        $seen = [];
        do {
            $reply = $this->request('/v3/posting/fbo/list', [
                'filter' => ['order_numbers' => [$orderNumber]],
                'limit' => 100,
                'cursor' => $cursor,
            ]);
            $rows = $reply['result'] ?? $reply['postings'] ?? null;
            if (!is_array($rows) || !array_is_list($rows)) {
                throw new OzonException('Ozon не вернул список отправлений.');
            }
            array_push($postings, ...$rows);
            $cursor = (string) ($reply['cursor'] ?? '');
            if (!empty($reply['has_next']) && ($cursor === '' || $rows === [])) {
                throw new OzonException('Ozon вернул неполную страницу отправлений.');
            }
            if ($cursor !== '' && isset($seen[$cursor])) {
                throw new OzonException('Ozon вернул повторяющуюся страницу отправлений.');
            }
            $seen[$cursor] = true;
        } while ($cursor !== '' && $rows !== []);
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

    public function partialReturns(string $orderNumber, array $postings): array
    {
        $last = 0;
        $quantities = [];
        do {
            $result = $this->request('/v1/returns/list', ['filter' => ['posting_numbers' => $postings], 'limit' => 100, 'last_id' => $last]);
            if (!is_array($result['returns'] ?? null)) {
                throw new OzonException('Ozon не вернул список возвратов.');
            }
            $next = $last;
            foreach ($result['returns'] as $return) {
                $next = max($next, (int)($return['id'] ?? 0));
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

    private function quantities(array $items): array
    {
        $quantities = [];
        foreach ($items as $item) {
            $sku = (int) $item['sku'];
            $quantities[$sku] = ($quantities[$sku] ?? 0) + (int) $item['quantity'];
        }
        ksort($quantities);
        return $quantities;
    }
}
