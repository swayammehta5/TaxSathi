'use client';

import { useMemo } from 'react';
import { useTranslations } from 'next-intl';
import { computeInvoiceTotals } from '@/lib/gstCalculator';

const money = new Intl.NumberFormat('en-IN', {
  style: 'currency',
  currency: 'INR',
  maximumFractionDigits: 2,
});

export default function InvoiceTotalsPreview({ lines, taxMode, cgstPct, sgstPct, igstPct, roundOff = '' }) {
  const t = useTranslations('Invoices');

  const totals = useMemo(
    () =>
      computeInvoiceTotals(
        lines,
        taxMode,
        {
          cgst_percent: Number(cgstPct),
          sgst_percent: Number(sgstPct),
          igst_percent: Number(igstPct),
        },
        roundOff === '' ? null : Number(roundOff)
      ),
    [lines, taxMode, cgstPct, sgstPct, igstPct, roundOff]
  );

  return (
    <div className="bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-slate-200 dark:border-slate-700 p-4 space-y-1 text-right text-sm">
      <p>
        {t('taxableAmount')}: <span className="font-medium">{money.format(totals.taxable)}</span>
      </p>
      {taxMode === 'intra_state' ? (
        <>
          <p>
            {t('cgstLine', { pct: cgstPct })}:{' '}
            <span className="font-medium">{money.format(totals.cgst)}</span>
          </p>
          <p>
            {t('sgstLine', { pct: sgstPct })}:{' '}
            <span className="font-medium">{money.format(totals.sgst)}</span>
          </p>
        </>
      ) : (
        <p>
          {t('igstLine', { pct: igstPct })}:{' '}
          <span className="font-medium">{money.format(totals.igst)}</span>
        </p>
      )}
      <p>
        {t('roundOff')}: <span className="font-medium">{money.format(totals.roundOff)}</span>
      </p>
      <p className="text-lg font-bold text-slate-900 dark:text-white pt-2">
        {t('grandTotal')}: {money.format(totals.grandTotal)}
      </p>
    </div>
  );
}
