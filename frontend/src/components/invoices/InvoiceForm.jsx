'use client';

import { useTranslations } from 'next-intl';
import { Link } from '@/i18n/routing';
import { Plus, Trash2 } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';
import api from '@/lib/axios';
import { computeJobWorkSummary, detectTaxMode, lineAmount } from '@/lib/gstCalculator';
import {
  buildPayload,
  emptyLine,
  stateFromInvoice,
} from '@/lib/invoiceFormState';
import AddFirmModal from '@/components/firms/AddFirmModal';
import { useRole } from '@/hooks/useRole';
import { normalizeFirms } from '@/lib/firms';

const money = new Intl.NumberFormat('en-IN', {
  style: 'currency',
  currency: 'INR',
  maximumFractionDigits: 2,
});

const inputClass =
  'w-full px-3 py-2 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-sm';

function Field({ label, children, className = '' }) {
  return (
    <div className={className}>
      <label className="block text-xs text-slate-600 dark:text-slate-400 mb-1">{label}</label>
      {children}
    </div>
  );
}

export default function InvoiceForm({ initialInvoice, onSubmit, submitLabel, cancelHref = '/invoices' }) {
  const t = useTranslations('Invoices');
  const { isAdmin } = useRole();
  const isEdit = Boolean(initialInvoice?.id);

  const [firms, setFirms] = useState([]);
  const [showFirmModal, setShowFirmModal] = useState(false);
  const [form, setForm] = useState(() => stateFromInvoice(initialInvoice));
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (initialInvoice?.id) {
      setForm(stateFromInvoice(initialInvoice));
    }
  }, [initialInvoice]);

  const setCustomer = (patch) => setForm((s) => ({ ...s, customer: { ...s.customer, ...patch } }));
  const setInfo = (patch) => setForm((s) => ({ ...s, info: { ...s.info, ...patch } }));
  const setBank = (patch) => setForm((s) => ({ ...s, bank: { ...s.bank, ...patch } }));
  const setSignatory = (patch) => setForm((s) => ({ ...s, signatory: { ...s.signatory, ...patch } }));

  const fetchFirms = useCallback(async () => {
    try {
      const { data } = await api.get('/firms');
      setFirms(normalizeFirms(data));
    } catch {
      toast.error(t('firmLoadError'));
      setFirms([]);
    }
  }, [t]);

  useEffect(() => {
    fetchFirms();
    if (!isEdit && !form.info.invoice_number) {
      const y = new Date().getFullYear();
      setInfo({ invoice_number: `INV-${y}-${String(Math.floor(Math.random() * 9000) + 1000)}` });
    }
  }, [fetchFirms, isEdit, form.info.invoice_number]);

  const onFirmCreated = (firm) => {
    if (!firm?.id) return;
    const normalized = { ...firm, id: String(firm.id) };
    setFirms((prev) => {
      const exists = prev.some((f) => String(f.id) === normalized.id);
      const next = exists ? prev : [...prev, normalized].sort((a, b) => a.name.localeCompare(b.name));
      return next;
    });
    setForm((s) => ({ ...s, firmId: normalized.id }));
  };

  const selectedFirm = firms.find((f) => String(f.id) === form.firmId);
  const taxMode = useMemo(
    () => detectTaxMode(selectedFirm?.gstin, form.customer.gstin, form.customer.state_code),
    [selectedFirm?.gstin, form.customer.gstin, form.customer.state_code]
  );

  const totals = useMemo(
    () =>
      computeJobWorkSummary(
        form.lines,
        {
          otherCharges: form.otherCharges,
          discount: form.discount,
          cgstPercent: form.cgstPct,
          sgstPercent: form.sgstPct,
          igstPercent: form.igstPct,
        },
        taxMode,
        form.roundOff === '' ? null : form.roundOff
      ),
    [form.lines, form.otherCharges, form.discount, form.cgstPct, form.sgstPct, form.igstPct, form.roundOff, taxMode]
  );

  const updateLine = (i, patch) => {
    setForm((s) => ({
      ...s,
      lines: s.lines.map((row, idx) => (idx === i ? { ...row, ...patch } : row)),
    }));
  };

  const onSignatureFile = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    if (file.size > 500000) {
      toast.error(t('signatureTooLarge'));
      return;
    }
    const reader = new FileReader();
    reader.onload = () => setSignatory({ signature: String(reader.result) });
    reader.readAsDataURL(file);
  };

  const submit = async (e) => {
    e.preventDefault();
    if (!form.firmId) {
      toast.error(t('selectFirmError'));
      return;
    }
    if (!form.customer.customer_name.trim()) {
      toast.error(t('customerNameRequired'));
      return;
    }
    const payload = buildPayload(form, totals, taxMode);
    if (payload.items.length === 0) {
      toast.error(t('lineItemsError'));
      return;
    }
    setSaving(true);
    try {
      await onSubmit(payload);
    } catch (err) {
      const errors = err?.response?.data?.errors;
      if (errors && typeof errors === 'object') {
        const first = Object.values(errors).flat()[0];
        if (first) toast.error(String(first));
      } else {
        const msg = err?.response?.data?.message ?? err?.message;
        if (msg) toast.error(String(msg));
      }
      throw err;
    } finally {
      setSaving(false);
    }
  };

  return (
    <>
    <AddFirmModal
      open={showFirmModal}
      onClose={() => setShowFirmModal(false)}
      onCreated={onFirmCreated}
    />
    <form onSubmit={submit} className="max-w-6xl space-y-6">
      <div className="bg-white dark:bg-slate-800 rounded-xl border-2 border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div className="bg-slate-800 text-white px-6 py-3 text-center font-bold tracking-wide">
          {t('taxInvoiceTitle')}
        </div>

        <div className="p-4 md:p-6 border-b border-slate-200 dark:border-slate-700">
          <div className="grid lg:grid-cols-2 gap-6">
            <div className="space-y-3">
              <p className="text-xs font-bold uppercase text-slate-500">{t('seller')}</p>
              <Field label={t('firm')}>
                <div className="flex gap-2">
                  <select
                    required
                    value={form.firmId}
                    onChange={(e) => setForm((s) => ({ ...s, firmId: e.target.value }))}
                    className={inputClass}
                  >
                    <option value="">{t('selectFirm')}</option>
                    {firms.map((f) => (
                      <option key={String(f.id)} value={String(f.id)}>
                        {f.name}
                      </option>
                    ))}
                  </select>
                  {isAdmin && (
                    <button
                      type="button"
                      onClick={() => setShowFirmModal(true)}
                      className="shrink-0 px-3 py-2 text-sm font-medium text-blue-600 border border-blue-200 dark:border-blue-800 rounded hover:bg-blue-50 dark:hover:bg-blue-950/40 whitespace-nowrap"
                    >
                      {t('newFirm')}
                    </button>
                  )}
                </div>
              </Field>
              {selectedFirm && (
                <div className="text-sm text-slate-600 dark:text-slate-400 pl-1 space-y-0.5">
                  <p className="font-semibold text-slate-800 dark:text-slate-200">{selectedFirm.name}</p>
                  {selectedFirm.gstin && <p>GSTIN: {selectedFirm.gstin}</p>}
                  {selectedFirm.address && <p className="whitespace-pre-line">{selectedFirm.address}</p>}
                </div>
              )}
            </div>

            <div className="space-y-3">
              <p className="text-xs font-bold uppercase text-slate-500">{t('customerDetails')}</p>
              <div className="grid sm:grid-cols-2 gap-3">
                <Field label={t('customerName')}>
                  <input
                    required
                    value={form.customer.customer_name}
                    onChange={(e) => setCustomer({ customer_name: e.target.value })}
                    className={inputClass}
                  />
                </Field>
                <Field label={t('companyName')}>
                  <input
                    value={form.customer.company_name}
                    onChange={(e) => setCustomer({ company_name: e.target.value })}
                    className={inputClass}
                  />
                </Field>
                <Field label={t('contactNumber')} className="sm:col-span-1">
                  <input
                    value={form.customer.contact_number}
                    onChange={(e) => setCustomer({ contact_number: e.target.value })}
                    className={inputClass}
                  />
                </Field>
                <Field label={t('email')}>
                  <input
                    type="email"
                    value={form.customer.email}
                    onChange={(e) => setCustomer({ email: e.target.value })}
                    className={inputClass}
                  />
                </Field>
                <Field label={t('gstNumber')}>
                  <input
                    value={form.customer.gstin}
                    onChange={(e) => setCustomer({ gstin: e.target.value.toUpperCase() })}
                    className={inputClass}
                  />
                </Field>
                <Field label={t('state')}>
                  <input
                    value={form.customer.state}
                    onChange={(e) => setCustomer({ state: e.target.value })}
                    className={inputClass}
                  />
                </Field>
                <Field label={t('stateCode')}>
                  <input
                    value={form.customer.state_code}
                    onChange={(e) => setCustomer({ state_code: e.target.value })}
                    className={inputClass}
                    maxLength={2}
                  />
                </Field>
              </div>
              <Field label={t('billingAddress')}>
                <textarea
                  rows={2}
                  value={form.customer.billing_address}
                  onChange={(e) => setCustomer({ billing_address: e.target.value })}
                  className={inputClass}
                />
              </Field>
              <Field label={t('shippingAddress')}>
                <textarea
                  rows={2}
                  value={form.customer.shipping_address}
                  onChange={(e) => setCustomer({ shipping_address: e.target.value })}
                  className={inputClass}
                />
              </Field>
            </div>
          </div>
        </div>

        <div className="p-4 md:p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/30">
          <p className="text-xs font-bold uppercase text-slate-500 mb-3">{t('invoiceInformation')}</p>
          <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <Field label={t('invoiceNumber')}>
              <input
                required
                value={form.info.invoice_number}
                onChange={(e) => setInfo({ invoice_number: e.target.value })}
                className={inputClass}
              />
            </Field>
            <Field label={t('invoiceDate')}>
              <input
                type="date"
                required
                value={form.info.invoice_date}
                onChange={(e) => setInfo({ invoice_date: e.target.value })}
                className={inputClass}
              />
            </Field>
            <Field label={t('dueDate')}>
              <input
                type="date"
                value={form.info.due_date}
                onChange={(e) => setInfo({ due_date: e.target.value })}
                className={inputClass}
              />
            </Field>
            <Field label={t('poNumber')}>
              <input
                value={form.info.po_number}
                onChange={(e) => setInfo({ po_number: e.target.value })}
                className={inputClass}
              />
            </Field>
            <Field label={t('deliveryChallan')}>
              <input
                value={form.info.delivery_challan_number}
                onChange={(e) => setInfo({ delivery_challan_number: e.target.value })}
                className={inputClass}
              />
            </Field>
            <Field label={t('paymentTerms')}>
              <input
                value={form.info.payment_terms}
                onChange={(e) => setInfo({ payment_terms: e.target.value })}
                className={inputClass}
              />
            </Field>
            <Field label={t('placeOfSupply')} className="sm:col-span-2">
              <input
                value={form.info.place_of_supply}
                onChange={(e) => setInfo({ place_of_supply: e.target.value })}
                className={inputClass}
              />
            </Field>
          </div>
          <p className="mt-2 text-xs text-slate-500">
            {t('taxType')}:{' '}
            <span className="font-medium">{taxMode === 'intra_state' ? t('intraState') : t('interState')}</span>
          </p>
        </div>

        <div className="p-4 md:p-6 border-b border-slate-200 dark:border-slate-700">
          <div className="flex justify-between items-center mb-3">
            <p className="text-xs font-bold uppercase text-slate-500">{t('jobWorkTable')}</p>
            <button
              type="button"
              onClick={() => setForm((s) => ({ ...s, lines: [...s.lines, emptyLine()] }))}
              className="inline-flex items-center gap-1 text-sm text-blue-600 font-medium"
            >
              <Plus className="w-4 h-4" /> {t('addLine')}
            </button>
          </div>
          <div className="overflow-x-auto -mx-2">
            <table className="w-full min-w-[900px] text-xs border border-slate-200 dark:border-slate-700">
              <thead className="bg-slate-100 dark:bg-slate-900">
                <tr>
                  <th className="p-2 border">{t('sno')}</th>
                  <th className="p-2 border text-left">{t('description')}</th>
                  <th className="p-2 border">PL</th>
                  <th className="p-2 border">TH</th>
                  <th className="p-2 border">HRS/Set</th>
                  <th className="p-2 border">{t('totalHrs')}</th>
                  <th className="p-2 border">{t('hsn')}</th>
                  <th className="p-2 border">{t('qty')}</th>
                  <th className="p-2 border">{t('rate')}</th>
                  <th className="p-2 border text-right">{t('lineAmount')}</th>
                  <th className="p-2 border w-8" />
                </tr>
              </thead>
              <tbody>
                {form.lines.map((line, i) => (
                  <tr key={i}>
                    <td className="p-1 border text-center text-slate-500">{i + 1}</td>
                    <td className="p-1 border">
                      <input
                        value={line.description}
                        onChange={(e) => updateLine(i, { description: e.target.value })}
                        className={inputClass}
                      />
                    </td>
                    <td className="p-1 border">
                      <input
                        type="number"
                        min={0}
                        step="0.01"
                        value={line.pl}
                        onChange={(e) => updateLine(i, { pl: e.target.value })}
                        className={`${inputClass} w-16`}
                      />
                    </td>
                    <td className="p-1 border">
                      <input
                        type="number"
                        min={0}
                        step="0.01"
                        value={line.th}
                        onChange={(e) => updateLine(i, { th: e.target.value })}
                        className={`${inputClass} w-16`}
                      />
                    </td>
                    <td className="p-1 border">
                      <input
                        type="number"
                        min={0}
                        step="0.01"
                        value={line.hrs_set}
                        onChange={(e) => updateLine(i, { hrs_set: e.target.value })}
                        className={`${inputClass} w-16`}
                      />
                    </td>
                    <td className="p-1 border">
                      <input
                        type="number"
                        min={0}
                        step="0.01"
                        value={line.total_hrs}
                        onChange={(e) => updateLine(i, { total_hrs: e.target.value })}
                        className={`${inputClass} w-16`}
                      />
                    </td>
                    <td className="p-1 border">
                      <input
                        value={line.hsn_sac_code}
                        onChange={(e) => updateLine(i, { hsn_sac_code: e.target.value })}
                        className={`${inputClass} w-20`}
                      />
                    </td>
                    <td className="p-1 border">
                      <input
                        type="number"
                        min={0}
                        step="0.01"
                        value={line.quantity}
                        onChange={(e) => updateLine(i, { quantity: Number(e.target.value) })}
                        className={`${inputClass} w-16 text-right`}
                      />
                    </td>
                    <td className="p-1 border">
                      <input
                        type="number"
                        min={0}
                        step="0.01"
                        value={line.rate}
                        onChange={(e) => updateLine(i, { rate: Number(e.target.value) })}
                        className={`${inputClass} w-20 text-right`}
                      />
                    </td>
                    <td className="p-2 border text-right font-medium whitespace-nowrap">
                      {money.format(lineAmount(line))}
                    </td>
                    <td className="p-1 border text-center">
                      <button
                        type="button"
                        disabled={form.lines.length <= 1}
                        onClick={() =>
                          setForm((s) => ({ ...s, lines: s.lines.filter((_, idx) => idx !== i) }))
                        }
                        className="text-red-600 disabled:opacity-30"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        <div className="p-4 md:p-6 grid lg:grid-cols-2 gap-6 border-b border-slate-200 dark:border-slate-700">
          <div className="space-y-3">
            <p className="text-xs font-bold uppercase text-slate-500">{t('jobWorkSummary')}</p>
            <div className="grid grid-cols-2 gap-3">
              <Field label={t('otherCharges')}>
                <input
                  type="number"
                  min={0}
                  step="0.01"
                  value={form.otherCharges}
                  onChange={(e) => setForm((s) => ({ ...s, otherCharges: e.target.value }))}
                  className={inputClass}
                />
              </Field>
              <Field label={t('discount')}>
                <input
                  type="number"
                  min={0}
                  step="0.01"
                  value={form.discount}
                  onChange={(e) => setForm((s) => ({ ...s, discount: e.target.value }))}
                  className={inputClass}
                />
              </Field>
            </div>
            {taxMode === 'intra_state' ? (
              <div className="grid grid-cols-2 gap-3">
                <Field label={t('cgstPct')}>
                  <input
                    type="number"
                    min={0}
                    value={form.cgstPct}
                    onChange={(e) => setForm((s) => ({ ...s, cgstPct: e.target.value }))}
                    className={inputClass}
                  />
                </Field>
                <Field label={t('sgstPct')}>
                  <input
                    type="number"
                    min={0}
                    value={form.sgstPct}
                    onChange={(e) => setForm((s) => ({ ...s, sgstPct: e.target.value }))}
                    className={inputClass}
                  />
                </Field>
              </div>
            ) : (
              <Field label={t('igstPct')}>
                <input
                  type="number"
                  min={0}
                  value={form.igstPct}
                  onChange={(e) => setForm((s) => ({ ...s, igstPct: e.target.value }))}
                  className={inputClass}
                />
              </Field>
            )}
          </div>

          <div className="bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-lg p-4 space-y-2 text-sm">
            <div className="flex justify-between">
              <span>{t('totalJobWork')}</span>
              <span>{money.format(totals.total_job_work_amount)}</span>
            </div>
            <div className="flex justify-between">
              <span>{t('otherCharges')}</span>
              <span>{money.format(totals.other_charges)}</span>
            </div>
            <div className="flex justify-between">
              <span>{t('discount')}</span>
              <span>- {money.format(totals.discount)}</span>
            </div>
            <div className="flex justify-between font-semibold border-t pt-2">
              <span>{t('taxableAmount')}</span>
              <span>{money.format(totals.taxable_amount)}</span>
            </div>
            {taxMode === 'intra_state' ? (
              <>
                <div className="flex justify-between">
                  <span>{t('cgstLine', { pct: form.cgstPct })}</span>
                  <span>{money.format(totals.cgst)}</span>
                </div>
                <div className="flex justify-between">
                  <span>{t('sgstLine', { pct: form.sgstPct })}</span>
                  <span>{money.format(totals.sgst)}</span>
                </div>
              </>
            ) : (
              <div className="flex justify-between">
                <span>{t('igstLine', { pct: form.igstPct })}</span>
                <span>{money.format(totals.igst)}</span>
              </div>
            )}
            <div className="flex justify-between items-center gap-2">
              <span>{t('roundOff')}</span>
              <input
                type="number"
                step="0.01"
                value={form.roundOff}
                onChange={(e) => setForm((s) => ({ ...s, roundOff: e.target.value }))}
                placeholder={String(totals.roundOff)}
                className="w-24 px-2 py-1 text-right rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900"
              />
            </div>
            <div className="flex justify-between text-lg font-bold border-t pt-2">
              <span>{t('grandTotal')}</span>
              <span>{money.format(totals.grandTotal)}</span>
            </div>
          </div>
        </div>

        <div className="p-4 md:p-6 border-b border-slate-200 dark:border-slate-700">
          <p className="text-xs font-bold uppercase text-slate-500 mb-3">{t('bankDetails')}</p>
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <Field label={t('accountHolder')}>
              <input
                value={form.bank.account_holder_name}
                onChange={(e) => setBank({ account_holder_name: e.target.value })}
                className={inputClass}
              />
            </Field>
            <Field label={t('bankName')}>
              <input
                value={form.bank.bank_name}
                onChange={(e) => setBank({ bank_name: e.target.value })}
                className={inputClass}
              />
            </Field>
            <Field label={t('branchName')}>
              <input
                value={form.bank.branch_name}
                onChange={(e) => setBank({ branch_name: e.target.value })}
                className={inputClass}
              />
            </Field>
            <Field label={t('accountNumber')}>
              <input
                value={form.bank.account_number}
                onChange={(e) => setBank({ account_number: e.target.value })}
                className={inputClass}
              />
            </Field>
            <Field label="IFSC">
              <input
                value={form.bank.ifsc_code}
                onChange={(e) => setBank({ ifsc_code: e.target.value.toUpperCase() })}
                className={inputClass}
              />
            </Field>
            <Field label={t('upiId')}>
              <input
                value={form.bank.upi_id}
                onChange={(e) => setBank({ upi_id: e.target.value })}
                className={inputClass}
              />
            </Field>
          </div>
        </div>

        <div className="p-4 md:p-6 space-y-4">
          <div className="text-xs text-slate-600 dark:text-slate-400 border border-dashed border-slate-300 dark:border-slate-600 p-3 rounded">
            <p className="font-bold uppercase text-slate-500 mb-1">{t('declaration')}</p>
            <p>{t('declarationText')}</p>
          </div>
          <div className="grid sm:grid-cols-2 gap-3">
            <Field label={t('authorizedPerson')}>
              <input
                value={form.signatory.authorized_person_name}
                onChange={(e) => setSignatory({ authorized_person_name: e.target.value })}
                className={inputClass}
              />
            </Field>
            <Field label={t('designation')}>
              <input
                value={form.signatory.designation}
                onChange={(e) => setSignatory({ designation: e.target.value })}
                className={inputClass}
              />
            </Field>
            <Field label={t('signatureUpload')} className="sm:col-span-2">
              <input type="file" accept="image/*" onChange={onSignatureFile} className="text-sm" />
              {form.signatory.signature && (
                <img src={form.signatory.signature} alt="" className="mt-2 h-16 object-contain" />
              )}
            </Field>
          </div>
        </div>
      </div>

      <div className="flex flex-wrap gap-3">
        <button
          type="submit"
          disabled={saving}
          className="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50"
        >
          {saving ? t('saving') : submitLabel}
        </button>
        <Link href={cancelHref} className="px-6 py-3 border border-slate-300 dark:border-slate-600 rounded-lg">
          {t('cancel')}
        </Link>
      </div>
    </form>
    </>
  );
}
