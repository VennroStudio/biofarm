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
        $rules = self::rules([], $rules);
        $gross = self::minor($input['amount'] ?? '0');
        $discount = self::minor($input['discountAmount'] ?? '0');
        $spent = self::minor($input['bonusAmount'] ?? '0');
        $costs = isset($input['costAmount']) && $input['costAmount'] !== '' ? self::minor($input['costAmount']) : null;
        $basis = $gross - $discount - $spent;
        if ($basis < 0) {
            throw new DomainException('Скидка и списание бонусов превышают стоимость товаров.');
        }
        $scenario = $input['scenario'] ?? 'team_customer';
        $rates = match ($scenario) {
            'partner_customer'  => [$rules['partnerDirectBps'], 0, 0],
            'team_customer'     => [$rules['memberDirectBps'], $rules['partnerTeamBps'], 0],
            'member_purchase'   => [0, $rules['partnerMemberBps'], 0],
            'ordinary_referral' => [0, 0, $rules['referralBonusBps']],
            default             => throw new DomainException('Неизвестный сценарий'),
        };
        $amounts = array_map(static fn (int $bps): int => intdiv($basis * $bps, 10000), [...$rates, $rules['buyerBps']]);
        $total = array_sum($amounts);
        $result = ['grossMinor' => $gross, 'discountMinor' => $discount, 'spentBonusMinor' => $spent, 'basisMinor' => $basis, 'directMinor' => $amounts[0], 'partnerMinor' => $amounts[1], 'referralBonusMinor' => $amounts[2], 'buyerMinor' => $amounts[3], 'totalMinor' => $total, 'totalIncentivesMinor' => $discount + $spent + $total, 'remainingBeforeCostsMinor' => $basis - $total];
        if ($costs !== null) {
            $result['costMinor'] = $costs;
            $result['remainingAfterCostsMinor'] = $basis - $total - $costs;
        }
        return $result;
    }

    public static function rules(array $input, array $current = []): array
    {
        // Preserve saved rates when splitting the old shared direct commission.
        $legacyDirect = $current['directBps'] ?? $current['levelsBps'][0] ?? 100;
        $current['partnerDirectBps'] ??= $legacyDirect;
        $current['partnerMemberBps'] ??= $legacyDirect;
        $current['memberDirectBps'] ??= $legacyDirect;
        $current['partnerTeamBps'] ??= $current['teamBps'] ?? $current['partnerBps'] ?? 50;
        $current['referralBonusBps'] ??= $legacyDirect;
        unset($current['maxPromoPercent'], $current['levelsBps'], $current['partnerBps'], $current['products'], $current['directBps'], $current['teamBps'], $current['capBps']);
        $defaults = ['partnerDirectBps' => 100, 'partnerMemberBps' => 100, 'partnerTeamBps' => 50, 'memberDirectBps' => 100, 'referralBonusBps' => 100, 'buyerBps' => 100, 'holdDays' => 14, 'minimumWithdrawalMinor' => 10000];
        if (array_diff(array_keys($input), array_keys($defaults)) !== []) {
            throw new DomainException('Unknown program setting');
        }
        $rules = array_replace($defaults, $current, $input);
        foreach (['partnerDirectBps', 'partnerMemberBps', 'partnerTeamBps', 'memberDirectBps', 'referralBonusBps', 'buyerBps'] as $key) {
            if (!\is_int($rules[$key]) || $rules[$key] < 0 || $rules[$key] > 10000) {
                throw new DomainException('Rates must be integer basis points from 0 to 10000');
            }
        }
        if (!\is_int($rules['holdDays']) || $rules['holdDays'] < 0 || $rules['holdDays'] > 3650 || !\is_int($rules['minimumWithdrawalMinor']) || $rules['minimumWithdrawalMinor'] < 1) {
            throw new DomainException('Invalid hold or withdrawal minimum');
        }
        return $rules;
    }
}
