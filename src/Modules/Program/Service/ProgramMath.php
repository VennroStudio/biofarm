<?php

declare(strict_types=1);

namespace App\Modules\Program\Service;

use DomainException;

final class ProgramMath
{
    public static function minor(mixed $value): int
    {
        if (!\is_scalar($value) || !preg_match('/^\d+(?:\.\d{1,2})?$/D', (string)$value)) {
            throw new DomainException('Amount must be a nonnegative decimal with at most two decimal places');
        }
        [$whole, $fraction] = array_pad(explode('.', (string)$value), 2, '');
        if (\strlen($whole) > 10) {
            throw new DomainException('Amount too large');
        }
        return (int)$whole * 100 + (int)str_pad($fraction, 2, '0');
    }

    /** Exact deterministic proportional allocation; stable input order breaks ties. */
    public static function allocate(int $amount, array $weights): array
    {
        $sum = array_sum($weights);
        if ($amount < 0 || $sum < $amount || min($weights ?: [0]) < 0) {
            throw new DomainException('Invalid allocation');
        }
        $out = array_fill_keys(array_keys($weights), 0);
        if ($sum === 0) {
            return $out;
        }
        $remainders = [];
        foreach ($weights as $key => $weight) {
            $out[$key] = intdiv($amount * $weight, $sum);
            $remainders[$key] = ($amount * $weight) % $sum;
        }
        arsort($remainders, SORT_NUMERIC);
        $left = $amount - array_sum($out);
        foreach ($remainders as $key => $_) {
            if ($left-- <= 0) {
                break;
            }
            ++$out[$key];
        }
        return $out;
    }

    public static function portion(int $amount, int $previous, int $quantity, int $total): int
    {
        if ($quantity < 1 || $previous < 0 || $previous + $quantity > $total) {
            throw new DomainException('Invalid refund quantity');
        }
        return intdiv($amount * ($previous + $quantity), $total) - intdiv($amount * $previous, $total);
    }

    /** Estimate bonuses and the amount remaining after costs supplied by the administrator. */
    public static function simulate(array $input, array $rules): array
    {
        $rules = self::rules([], $rules);
        $gross = self::minor($input['amount'] ?? '0');
        $discount = self::minor($input['discountAmount'] ?? '0');
        $spent = self::minor($input['bonusAmount'] ?? '0');
        $costs = isset($input['costAmount']) && $input['costAmount'] !== '' ? self::minor($input['costAmount']) : null;
        $basis = $gross - $discount - $spent;
        if ($basis < 0) {
            throw new DomainException('Скидка и списание бонусов превышают стоимость товаров.');
        }
        $scenario = $input['scenario'] ?? 'referral';
        [$buyerRate, $referralRate] = match ($scenario) {
            'purchase' => [$rules['buyerBps'], 0],
            'referral' => [$rules['buyerBps'], $rules['referralBonusBps']],
            'guest_referral' => [0, $rules['referralBonusBps']],
            default => throw new DomainException('Неизвестный сценарий'),
        };
        $buyer = intdiv($basis * $buyerRate, 10000);
        $referral = intdiv($basis * $referralRate, 10000);
        $total = $buyer + $referral;
        $result = ['grossMinor' => $gross, 'discountMinor' => $discount, 'spentBonusMinor' => $spent,
            'basisMinor' => $basis, 'referralBonusMinor' => $referral, 'buyerMinor' => $buyer,
            'totalMinor' => $total, 'totalIncentivesMinor' => $discount + $spent + $total,
            'remainingBeforeCostsMinor' => $basis - $total];
        if ($costs !== null) {
            $result['costMinor'] = $costs;
            $result['remainingAfterCostsMinor'] = $basis - $total - $costs;
        }
        return $result;
    }

    public static function rules(array $input, array $current = []): array
    {
        $defaults = ['referralBonusBps' => 100, 'buyerBps' => 300, 'holdDays' => 14];
        if (array_diff_key($input, $defaults) !== []) {
            throw new DomainException('Неизвестная настройка программы бонусов');
        }
        $current['referralBonusBps'] ??= $current['directBps'] ?? $current['levelsBps'][0] ?? $defaults['referralBonusBps'];
        $rules = array_replace($defaults, array_intersect_key($current, $defaults), $input);
        foreach (['referralBonusBps', 'buyerBps'] as $key) {
            if (!\is_int($rules[$key]) || $rules[$key] < 0 || $rules[$key] > 10000) {
                throw new DomainException('Ставка должна быть от 0 до 100%');
            }
        }
        if (!\is_int($rules['holdDays']) || $rules['holdDays'] < 0 || $rules['holdDays'] > 3650) {
            throw new DomainException('Некорректный срок удержания');
        }
        return $rules;
    }
}
