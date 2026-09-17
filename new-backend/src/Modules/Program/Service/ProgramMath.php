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

    /** Conservative full-chain estimate; costs are supplied explicitly, never guessed. */
    public static function simulate(array $input, array $rules): array
    {
        $gross = self::minor($input['amount'] ?? '0');
        $discount = self::minor($input['discountAmount'] ?? '0');
        $spent = self::minor($input['bonusAmount'] ?? '0');
        $costs = isset($input['costAmount']) && $input['costAmount'] !== '' ? self::minor($input['costAmount']) : null;
        $basis = $gross - $discount - $spent;
        if ($basis < 0) {
            throw new DomainException('Скидка и списание бонусов превышают стоимость товаров.');
        }
        $amounts = array_map(static fn (int $bps): int => intdiv($basis * $bps, 10000), [...$rules['levelsBps'], $rules['partnerBps'], $rules['buyerBps']]);
        $total = array_sum($amounts);
        $result = ['grossMinor' => $gross, 'discountMinor' => $discount, 'spentBonusMinor' => $spent, 'basisMinor' => $basis, 'levelsMinor' => \array_slice($amounts, 0, 2), 'partnerMinor' => $amounts[2], 'buyerMinor' => $amounts[3], 'totalMinor' => $total, 'capMinor' => intdiv($basis * $rules['capBps'], 10000), 'totalIncentivesMinor' => $discount + $spent + $total, 'remainingBeforeCostsMinor' => $basis - $total];
        if ($costs !== null) {
            $result['costMinor'] = $costs;
            $result['remainingAfterCostsMinor'] = $basis - $total - $costs;
        }
        return $result;
    }

    public static function rules(array $input, array $current = []): array
    {
        unset($current['maxPromoPercent']); // Historical settings remain readable.
        // Upgrade persisted settings; new writes must explicitly supply two rates.
        if (isset($current['levelsBps']) && \is_array($current['levelsBps']) && array_is_list($current['levelsBps']) && \count($current['levelsBps']) === 4) {
            $current['levelsBps'] = \array_slice($current['levelsBps'], 0, 2);
        }
        $rules = array_replace(['levelsBps' => [100, 50], 'partnerBps' => 100, 'buyerBps' => 100, 'capBps' => 400, 'holdDays' => 14, 'minimumWithdrawalMinor' => 10000, 'products' => []], $current, $input);
        if (array_diff(array_keys($input), ['levelsBps', 'partnerBps', 'buyerBps', 'capBps', 'holdDays', 'minimumWithdrawalMinor', 'products']) !== []) {
            throw new DomainException('Unknown program setting');
        }
        if (!\is_array($rules['products']) || !\is_array($rules['levelsBps']) || !array_is_list($rules['levelsBps']) || \count($rules['levelsBps']) !== 2) {
            throw new DomainException('Exactly two level rates required');
        }
        foreach ([...$rules['levelsBps'], $rules['partnerBps'], $rules['buyerBps'], $rules['capBps']] as $rate) {
            if (!\is_int($rate) || $rate < 0 || $rate > 10000) {
                throw new DomainException('Rates must be integer basis points from 0 to 10000');
            }
        }
        if (array_sum($rules['levelsBps']) + $rules['partnerBps'] + $rules['buyerBps'] > $rules['capBps']) {
            throw new DomainException('Maximum rewards exceed budget cap');
        }
        if (!\is_int($rules['holdDays']) || $rules['holdDays'] < 0 || $rules['holdDays'] > 3650 || !\is_int($rules['minimumWithdrawalMinor']) || $rules['minimumWithdrawalMinor'] < 1) {
            throw new DomainException('Invalid hold or withdrawal minimum');
        }
        foreach ($rules['products'] as $id => $factor) {
            if ((int)$id < 1 || !\is_int($factor) || $factor < 0 || $factor > 10000) {
                throw new DomainException('Product factors must be 0..10000 basis points');
            }
        }
        return $rules;
    }
}
