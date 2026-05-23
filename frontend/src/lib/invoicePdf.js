import api from '@/lib/axios';

function safeFilename(name) {
  const base = String(name || 'invoice').replace(/[^A-Za-z0-9._-]+/g, '_');
  return base.endsWith('.pdf') ? base : `${base}.pdf`;
}

async function parseBlobError(blob) {
  try {
    const text = await blob.text();
    const json = JSON.parse(text);
    return json?.message || 'Could not download PDF';
  } catch {
    return 'Could not download PDF';
  }
}

/** Download invoice PDF via authenticated API. */
export async function downloadInvoicePdf(invoiceId, filename = 'invoice.pdf') {
  try {
    const response = await api.get(`/invoices/${invoiceId}/download`, {
      responseType: 'blob',
      headers: {
        Accept: 'application/pdf',
      },
    });

    const contentType = String(response.headers['content-type'] || response.data?.type || '');

    if (contentType.includes('application/json') || response.data?.type === 'application/json') {
      throw new Error(await parseBlobError(response.data));
    }

    const blob =
      response.data instanceof Blob
        ? response.data
        : new Blob([response.data], { type: 'application/pdf' });

    if (blob.size < 100) {
      throw new Error(await parseBlobError(blob));
    }

    const url = window.URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = safeFilename(filename);
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    if (err.response?.data instanceof Blob) {
      throw new Error(await parseBlobError(err.response.data));
    }
    throw err;
  }
}
