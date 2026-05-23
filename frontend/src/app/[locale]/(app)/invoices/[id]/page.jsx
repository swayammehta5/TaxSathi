'use client';

import { useTranslations } from 'next-intl';
import { Link, useRouter } from '@/i18n/routing';
import { ArrowLeft, Mail, Trash2, Download, Pencil, Printer } from 'lucide-react';
import { useParams } from 'next/navigation';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';
import api from '@/lib/axios';
import { downloadInvoicePdf } from '@/lib/invoicePdf';
import { useRole } from '@/hooks/useRole';
import { confirmAction } from '@/components/ui/confirm';
import InvoicePrintLayout from '@/components/invoices/InvoicePrintLayout';

export default function InvoiceDetailPage() {
  const t = useTranslations('Invoices');
  const ti = useTranslations('Index');
  const params = useParams();
  const id = params?.id;
  const router = useRouter();
  const { isAdmin } = useRole();
  const [inv, setInv] = useState(null);
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [downloading, setDownloading] = useState(false);

  const load = useCallback(async () => {
    if (!id) return;
    setLoading(true);
    try {
      const { data } = await api.get(`/invoices/${id}`);
      setInv(data);
    } catch {
      toast.error(t('notFound'));
      setInv(null);
    } finally {
      setLoading(false);
    }
  }, [id, t]);

  useEffect(() => {
    load();
  }, [load]);

  const handlePrint = () => {
    window.print();
  };

  const sendMail = async (e) => {
    e.preventDefault();
    if (!email.trim()) {
      toast.error(t('emailRequired'));
      return;
    }
    setSending(true);
    try {
      await api.post(`/invoices/${id}/send-email`, { email: email.trim() });
      toast.success(t('emailSent'));
    } catch (err) {
      toast.error(err?.friendlyMessage || t('emailFail'));
    } finally {
      setSending(false);
    }
  };

  const downloadPdf = async () => {
    setDownloading(true);
    try {
      await downloadInvoicePdf(id, inv?.invoice_number ?? 'invoice');
      toast.success(t('pdfDownloaded'));
    } catch (err) {
      toast.error(err?.message || t('pdfFail'));
    } finally {
      setDownloading(false);
    }
  };

  const remove = async () => {
    if (!confirmAction(t('deleteConfirm'))) return;
    try {
      await api.delete(`/invoices/${id}`);
      toast.success(t('invoiceDeleted'));
      router.push('/invoices');
    } catch (err) {
      toast.error(err?.friendlyMessage || t('deleteFail'));
    }
  };

  if (loading) {
    return <p className="py-20 text-center text-slate-500">{ti('loading')}</p>;
  }

  if (!inv) {
    return (
      <section className="space-y-4 no-print">
        <Link href="/invoices" className="text-blue-600 text-sm">
          ← {t('detailBack')}
        </Link>
        <p className="text-slate-600">{t('notFound')}</p>
      </section>
    );
  }

  return (
    <section className="max-w-6xl">
      <style jsx global>{`
        @media print {
          body * {
            visibility: hidden;
          }
          #invoice-print-root,
          #invoice-print-root * {
            visibility: visible;
          }
          #invoice-print-root {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 12px;
          }
          .no-print {
            display: none !important;
          }
        }
      `}</style>

      <div className="no-print">
        <Link
          href="/invoices"
          className="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400 hover:text-blue-600 mb-6"
        >
          <ArrowLeft className="w-4 h-4" /> {t('detailBack')}
        </Link>

        <header className="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
          <h1 className="text-2xl font-bold text-slate-800 dark:text-white">{inv.invoice_number}</h1>
          <div className="flex flex-wrap gap-2">
            <button
              type="button"
              onClick={handlePrint}
              className="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 text-sm"
            >
              <Printer className="w-4 h-4" /> {t('printInvoice')}
            </button>
            {isAdmin && (
              <Link
                href={`/invoices/${id}/edit`}
                className="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 text-sm"
              >
                <Pencil className="w-4 h-4" /> {t('edit')}
              </Link>
            )}
            <button
              type="button"
              onClick={downloadPdf}
              disabled={downloading}
              className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm disabled:opacity-50"
            >
              <Download className="w-4 h-4" /> {downloading ? t('downloading') : t('downloadPdf')}
            </button>
            {isAdmin && (
              <button
                type="button"
                onClick={remove}
                className="inline-flex items-center gap-2 px-4 py-2 text-red-600 border border-red-200 dark:border-red-900 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/30 text-sm"
              >
                <Trash2 className="w-4 h-4" /> {t('delete')}
              </button>
            )}
          </div>
        </header>

        {isAdmin && (
          <article className="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 mb-6">
            <h2 className="font-semibold text-slate-800 dark:text-white flex items-center gap-2 mb-4">
              <Mail className="w-5 h-5" /> {t('sendPdf')}
            </h2>
            <form onSubmit={sendMail} className="flex flex-col sm:flex-row gap-3">
              <input
                type="email"
                placeholder={t('emailPlaceholder')}
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="flex-1 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900"
              />
              <button
                type="submit"
                disabled={sending}
                className="px-5 py-2 bg-blue-600 text-white rounded-lg font-medium disabled:opacity-50"
              >
                {sending ? t('sending') : t('send')}
              </button>
            </form>
          </article>
        )}
      </div>

      <InvoicePrintLayout invoice={inv} className="rounded-xl border border-slate-200 dark:border-slate-700 p-4 md:p-6 shadow-sm" />
    </section>
  );
}
