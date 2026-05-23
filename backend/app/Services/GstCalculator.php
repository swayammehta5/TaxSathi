<?php

namespace App\Services;

class GstCalculator
{
    /**
     * @param  array{cgst_percent?: float, sgst_percent?: float, igst_percent?: float}  $rates
     * @return array{
     *     cgst: float, sgst: float, igst: float, round_off: float, total_amount: float,
     *     cgst_percent: float, sgst_percent: float, igst_percent: float
     * }
     */
    public static function computeJobWorkSummary(
        float $totalJobWorkAmount,
        float $otherCharges,
        float $discount,
        string $taxMode,
        array $rates,
        ?float $roundOff = null
    ): array {
        $taxable = round(max(0, $totalJobWorkAmount + $otherCharges - $discount), 2);
        $cgstPct = (float) ($rates['cgst_percent'] ?? 9);
        $sgstPct = (float) ($rates['sgst_percent'] ?? 9);
        $igstPct = (float) ($rates['igst_percent'] ?? 18);

        if ($taxMode === 'inter_state') {
            $cgst = 0.0;
            $sgst = 0.0;
            $igst = round($taxable * ($igstPct / 100), 2);
        } else {
            $cgst = round($taxable * ($cgstPct / 100), 2);
            $sgst = round($taxable * ($sgstPct / 100), 2);
            $igst = 0.0;
        }

        $beforeRound = $taxable + $cgst + $sgst + $igst;
        $appliedRoundOff = $roundOff ?? round(round($beforeRound) - $beforeRound, 2);

        return [
            'total_job_work_amount' => round($totalJobWorkAmount, 2),
            'other_charges' => round($otherCharges, 2),
            'discount' => round($discount, 2),
            'taxable_amount' => $taxable,
            'cgst_percent' => $cgstPct,
            'sgst_percent' => $sgstPct,
            'igst_percent' => $igstPct,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'igst' => $igst,
            'round_off' => $appliedRoundOff,
            'total_amount' => round($beforeRound + $appliedRoundOff, 2),
        ];
    }

    public static function detectTaxMode(?string $sellerGstin, ?string $buyerGstin, ?string $buyerStateCode = null): string
    {
        $seller = self::stateCode($sellerGstin);
        $buyer = $buyerStateCode ?: self::stateCode($buyerGstin);

        if ($buyer === null || $seller === null) {
            return 'intra_state';
        }

        return $seller === $buyer ? 'intra_state' : 'inter_state';
    }

    private static function stateCode(?string $gstin): ?string
    {
        $gstin = strtoupper(trim((string) $gstin));

        return strlen($gstin) >= 2 ? substr($gstin, 0, 2) : null;
    }
}
