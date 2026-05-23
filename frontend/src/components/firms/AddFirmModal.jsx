'use client';

import { useTranslations } from 'next-intl';
import { useState } from 'react';
import { toast } from 'sonner';
import api from '@/lib/axios';

const emptyForm = () => ({
  name: '',
  gstin: '',
  address: '',
  state: '',
  state_code: '',
  contact_number: '',
  email: '',
});

const inputClass =
  'w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-sm';

export default function AddFirmModal({ open, onClose, onCreated }) {
  const t = useTranslations('Invoices');
  const [form, setForm] = useState(emptyForm);
  const [saving, setSaving] = useState(false);

  if (!open) return null;

  const set = (patch) => setForm((s) => ({ ...s, ...patch }));

  const submit = async (e) => {
    e.preventDefault();
    if (!form.name.trim() || !form.gstin.trim() || !form.address.trim()) {
      toast.error(t('firmFieldsRequired'));
      return;
    }
    setSaving(true);
    try {
      const { data } = await api.post('/firms', {
        name: form.name.trim(),
        gstin: form.gstin.trim().toUpperCase(),
        address: form.address.trim(),
        state: form.state.trim() || null,
        state_code: form.state_code.trim() || null,
        contact_number: form.contact_number.trim() || null,
        email: form.email.trim() || null,
      });
      const firm = data?.firm ?? data;
      toast.success(t('firmSaved'));
      setForm(emptyForm());
      onCreated?.(firm);
      onClose();
    } catch (err) {
      const errors = err?.response?.data?.errors;
      if (errors && typeof errors === 'object') {
        const first = Object.values(errors).flat()[0];
        toast.error(first ? String(first) : t('firmSaveError'));
      } else {
        toast.error(err?.response?.data?.message ?? t('firmSaveError'));
      }
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" role="dialog" aria-modal="true">
      <div className="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div className="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
          <h2 className="text-lg font-semibold text-slate-800 dark:text-white">{t('addFirmTitle')}</h2>
          <button
            type="button"
            onClick={onClose}
            className="text-slate-500 hover:text-slate-800 dark:hover:text-white text-xl leading-none"
            aria-label={t('cancel')}
          >
            ×
          </button>
        </div>
        <form onSubmit={submit} className="p-4 space-y-3">
          <div>
            <label className="text-xs text-slate-600 dark:text-slate-400">{t('firmNamePlaceholder')}</label>
            <input
              required
              value={form.name}
              onChange={(e) => set({ name: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className="text-xs text-slate-600 dark:text-slate-400">GSTIN *</label>
            <input
              required
              value={form.gstin}
              onChange={(e) => set({ gstin: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className="text-xs text-slate-600 dark:text-slate-400">{t('addressPlaceholder')}</label>
            <textarea
              required
              rows={2}
              value={form.address}
              onChange={(e) => set({ address: e.target.value })}
              className={inputClass}
            />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="text-xs text-slate-600 dark:text-slate-400">{t('state')}</label>
              <input value={form.state} onChange={(e) => set({ state: e.target.value })} className={inputClass} />
            </div>
            <div>
              <label className="text-xs text-slate-600 dark:text-slate-400">{t('stateCode')}</label>
              <input
                value={form.state_code}
                onChange={(e) => set({ state_code: e.target.value })}
                maxLength={2}
                className={inputClass}
              />
            </div>
          </div>
          <div>
            <label className="text-xs text-slate-600 dark:text-slate-400">{t('contactNumber')}</label>
            <input
              value={form.contact_number}
              onChange={(e) => set({ contact_number: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className="text-xs text-slate-600 dark:text-slate-400">{t('email')}</label>
            <input
              type="email"
              value={form.email}
              onChange={(e) => set({ email: e.target.value })}
              className={inputClass}
            />
          </div>
          <div className="flex gap-2 pt-2">
            <button
              type="submit"
              disabled={saving}
              className="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50"
            >
              {saving ? t('saving') : t('saveFirm')}
            </button>
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg"
            >
              {t('cancel')}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
