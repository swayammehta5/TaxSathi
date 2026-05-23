'use client';

import { useTranslations } from 'next-intl';

const money = new Intl.NumberFormat('en-IN', {
  style: 'currency',
  currency: 'INR',
  maximumFractionDigits: 2,
});

const fmt = (n) => money.format(Number(n) || 0);

const DECLARATION =
  'We hereby certify that the particulars given above are true and correct. All goods/services mentioned in this invoice are supplied as per agreed terms.';

export default function InvoicePrintLayout({ invoice, className = '' }) {
  const t = useTranslations('Invoices');
  if (!invoice) return null;

  const cd = invoice.customer_details ?? {};
  const ii = invoice.invoice_info ?? {};
  const sm = invoice.summary ?? {};
  const bk = invoice.bank_details ?? {};
  const sg = invoice.signatory ?? {};
  const taxMode = invoice.tax_mode ?? 'intra_state';
  const items = invoice.items ?? [];

  const customerName = cd.customer_name ?? invoice.customer_name;
  const billing = cd.billing_address ?? invoice.customer_address;
  const gstin = cd.gstin ?? invoice.gst_number;

  return (
    <article
      id="invoice-print-root"
      className={`invoice-print bg-white text-slate-900 text-sm ${className}`}
    >
      <header className="text-center border-b-2 border-slate-800 pb-3 mb-4">
        <h1 className="text-xl font-bold tracking-widest">{t('taxInvoiceTitle')}</h1>
        {invoice.firm && <p className="font-semibold mt-1 text-base">{invoice.firm.name}</p>}
      </header>

      <div className="grid md:grid-cols-2 gap-4 mb-4">
        <section className="border border-slate-300 p-3 rounded">
          <p className="text-xs font-bold uppercase text-slate-500 mb-2">{t('seller')}</p>
          {invoice.firm && (
            <>
              <p className="font-semibold">{invoice.firm.name}</p>
              {invoice.firm.gstin && <p>GSTIN: {invoice.firm.gstin}</p>}
              {invoice.firm.address && <p className="whitespace-pre-line">{invoice.firm.address}</p>}
              {invoice.firm.phone && <p>{invoice.firm.phone}</p>}
            </>
          )}
        </section>
        <section className="border border-slate-300 p-3 rounded">
          <p className="text-xs font-bold uppercase text-slate-500 mb-2">{t('customerDetails')}</p>
          <p className="font-semibold">{customerName}</p>
          {cd.company_name && <p>{cd.company_name}</p>}
          {billing && <p className="whitespace-pre-line">{billing}</p>}
          {cd.shipping_address && (
            <p className="mt-1">
              <span className="text-slate-500">{t('shippingAddress')}: </span>
              {cd.shipping_address}
            </p>
          )}
          {cd.contact_number && <p>{t('contactNumber')}: {cd.contact_number}</p>}
          {cd.email && <p>{cd.email}</p>}
          {gstin && <p>GSTIN: {gstin}</p>}
          {(cd.state || cd.state_code) && (
            <p>
              {t('state')}: {cd.state} {cd.state_code ? `(${cd.state_code})` : ''}
            </p>
          )}
        </section>
      </div>

      <section className="grid sm:grid-cols-2 lg:grid-cols-4 gap-2 mb-4 text-xs border border-slate-300 p-3 rounded">
        <p>
          <span className="text-slate-500">{t('invoiceNumber')}:</span>{' '}
          <strong>{ii.invoice_number ?? invoice.invoice_number}</strong>
        </p>
        <p>
          <span className="text-slate-500">{t('invoiceDate')}:</span>{' '}
          {ii.invoice_date ?? invoice.date}
        </p>
        <p>
          <span className="text-slate-500">{t('dueDate')}:</span> {ii.due_date || '—'}
        </p>
        <p>
          <span className="text-slate-500">{t('poNumber')}:</span> {ii.po_number || '—'}
        </p>
        <p>
          <span className="text-slate-500">{t('deliveryChallan')}:</span>{' '}
          {ii.delivery_challan_number || '—'}
        </p>
        <p>
          <span className="text-slate-500">{t('paymentTerms')}:</span> {ii.payment_terms || '—'}
        </p>
        <p className="sm:col-span-2">
          <span className="text-slate-500">{t('placeOfSupply')}:</span>{' '}
          {ii.place_of_supply || '—'}
        </p>
      </section>

      <div className="overflow-x-auto mb-4">
        <table className="w-full border-collapse border border-slate-300 text-xs">
          <thead>
            <tr className="bg-slate-100">
              <th className="border border-slate-300 p-2">{t('sno')}</th>
              <th className="border border-slate-300 p-2 text-left">{t('description')}</th>
              <th className="border border-slate-300 p-2">PL</th>
              <th className="border border-slate-300 p-2">TH</th>
              <th className="border border-slate-300 p-2">HRS/Set</th>
              <th className="border border-slate-300 p-2">{t('totalHrs')}</th>
              <th className="border border-slate-300 p-2">{t('hsn')}</th>
              <th className="border border-slate-300 p-2">{t('qty')}</th>
              <th className="border border-slate-300 p-2">{t('rate')}</th>
              <th className="border border-slate-300 p-2 text-right">{t('lineAmount')}</th>
            </tr>
          </thead>
          <tbody>
            {items.map((row, i) => (
              <tr key={row.id ?? i}>
                <td className="border border-slate-300 p-2 text-center">{i + 1}</td>
                <td className="border border-slate-300 p-2">{row.description}</td>
                <td className="border border-slate-300 p-2 text-center">{row.pl ?? '—'}</td>
                <td className="border border-slate-300 p-2 text-center">{row.th ?? '—'}</td>
                <td className="border border-slate-300 p-2 text-center">{row.hrs_set ?? '—'}</td>
                <td className="border border-slate-300 p-2 text-center">{row.total_hrs ?? '—'}</td>
                <td className="border border-slate-300 p-2">{row.hsn_sac_code ?? row.hsn ?? '—'}</td>
                <td className="border border-slate-300 p-2 text-right">{row.quantity}</td>
                <td className="border border-slate-300 p-2 text-right">{fmt(row.rate)}</td>
                <td className="border border-slate-300 p-2 text-right font-medium">{fmt(row.amount)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className="grid md:grid-cols-2 gap-4 mb-4">
        <section className="border border-slate-300 p-3 rounded text-xs">
          <p className="font-bold uppercase text-slate-500 mb-2">{t('bankDetails')}</p>
          <p>
            {t('accountHolder')}: {bk.account_holder_name || '—'}
          </p>
          <p>
            {t('bankName')}: {bk.bank_name || '—'}
          </p>
          <p>
            {t('branchName')}: {bk.branch_name || '—'}
          </p>
          <p>
            {t('accountNumber')}: {bk.account_number || '—'}
          </p>
          <p>IFSC: {bk.ifsc_code || '—'}</p>
          {bk.upi_id && <p>UPI: {bk.upi_id}</p>}
        </section>
        <section className="border border-slate-300 p-3 rounded text-sm space-y-1">
          <div className="flex justify-between">
            <span>{t('totalJobWork')}</span>
            <span>{fmt(sm.total_job_work_amount ?? invoice.total_job_work_amount)}</span>
          </div>
          <div className="flex justify-between">
            <span>{t('otherCharges')}</span>
            <span>{fmt(sm.other_charges ?? invoice.other_charges)}</span>
          </div>
          <div className="flex justify-between">
            <span>{t('discount')}</span>
            <span>- {fmt(sm.discount ?? invoice.discount)}</span>
          </div>
          <div className="flex justify-between font-semibold border-t pt-1">
            <span>{t('taxableAmount')}</span>
            <span>{fmt(sm.taxable_amount ?? invoice.taxable_amount ?? invoice.subtotal)}</span>
          </div>
          {taxMode === 'intra_state' ? (
            <>
              <div className="flex justify-between">
                <span>{t('cgstLine', { pct: invoice.cgst_percent ?? 9 })}</span>
                <span>{fmt(sm.cgst ?? invoice.cgst)}</span>
              </div>
              <div className="flex justify-between">
                <span>{t('sgstLine', { pct: invoice.sgst_percent ?? 9 })}</span>
                <span>{fmt(sm.sgst ?? invoice.sgst)}</span>
              </div>
            </>
          ) : (
            <div className="flex justify-between">
              <span>{t('igstLine', { pct: invoice.igst_percent ?? 18 })}</span>
              <span>{fmt(sm.igst ?? invoice.igst)}</span>
            </div>
          )}
          <div className="flex justify-between">
            <span>{t('roundOff')}</span>
            <span>{fmt(sm.round_off ?? invoice.round_off)}</span>
          </div>
          <div className="flex justify-between text-base font-bold border-t pt-2">
            <span>{t('grandTotal')}</span>
            <span>{fmt(sm.grand_total ?? invoice.total_amount)}</span>
          </div>
        </section>
      </div>

      <section className="border border-slate-300 p-3 rounded mb-4 text-xs text-slate-700">
        <p className="font-bold uppercase text-slate-500 mb-1">{t('declaration')}</p>
        <p>{DECLARATION}</p>
      </section>

      <footer className="flex justify-between items-end border-t border-slate-300 pt-4">
        <div />
        <div className="text-right text-sm">
          {sg.signature && (
            <img src={sg.signature} alt="" className="h-16 ml-auto mb-2 object-contain" />
          )}
          <p className="font-semibold border-t border-slate-800 pt-2 min-w-[200px]">
            {sg.authorized_person_name || '—'}
          </p>
          <p className="text-slate-600">{sg.designation || t('authorizedSignatory')}</p>
        </div>
      </footer>
    </article>
  );
}
