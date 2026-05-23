const emptyLine = () => ({
  description: '',
  pl: '',
  th: '',
  hrs_set: '',
  total_hrs: '',
  hsn_sac_code: '',
  quantity: 1,
  rate: 0,
});

export function emptyCustomer() {
  return {
    customer_name: '',
    company_name: '',
    billing_address: '',
    shipping_address: '',
    contact_number: '',
    email: '',
    gstin: '',
    state: '',
    state_code: '',
  };
}

export function emptyInvoiceInfo() {
  return {
    invoice_number: '',
    invoice_date: new Date().toISOString().slice(0, 10),
    due_date: '',
    po_number: '',
    delivery_challan_number: '',
    payment_terms: '',
    place_of_supply: '',
  };
}

export function emptyBank() {
  return {
    account_holder_name: '',
    bank_name: '',
    branch_name: '',
    account_number: '',
    ifsc_code: '',
    upi_id: '',
  };
}

export function emptySignatory() {
  return {
    authorized_person_name: '',
    designation: '',
    signature: '',
  };
}

export function stateFromInvoice(inv) {
  if (!inv) {
    return {
      firmId: '',
      customer: emptyCustomer(),
      info: emptyInvoiceInfo(),
      lines: [emptyLine()],
      otherCharges: '0',
      discount: '0',
      cgstPct: '9',
      sgstPct: '9',
      igstPct: '18',
      roundOff: '',
      bank: emptyBank(),
      signatory: emptySignatory(),
    };
  }

  const cd = inv.customer_details ?? {};
  const ii = inv.invoice_info ?? {};
  const sm = inv.summary ?? {};
  const bk = inv.bank_details ?? {};
  const sg = inv.signatory ?? {};

  return {
    firmId: String(inv.firm_id ?? ''),
    customer: {
      customer_name: cd.customer_name ?? inv.customer_name ?? '',
      company_name: cd.company_name ?? inv.company_name ?? '',
      billing_address: cd.billing_address ?? inv.customer_address ?? '',
      shipping_address: cd.shipping_address ?? '',
      contact_number: cd.contact_number ?? '',
      email: cd.email ?? '',
      gstin: cd.gstin ?? inv.gst_number ?? '',
      state: cd.state ?? '',
      state_code: cd.state_code ?? '',
    },
    info: {
      invoice_number: ii.invoice_number ?? inv.invoice_number ?? '',
      invoice_date: ii.invoice_date ?? ii.date ?? inv.date ?? '',
      due_date: ii.due_date ?? inv.due_date ?? '',
      po_number: ii.po_number ?? inv.po_number ?? '',
      delivery_challan_number: ii.delivery_challan_number ?? inv.delivery_challan_number ?? '',
      payment_terms: ii.payment_terms ?? inv.payment_terms ?? '',
      place_of_supply: ii.place_of_supply ?? inv.place_of_supply ?? '',
    },
    lines: inv.items?.length
      ? inv.items.map((row) => ({
          description: row.description ?? '',
          pl: row.pl ?? '',
          th: row.th ?? '',
          hrs_set: row.hrs_set ?? '',
          total_hrs: row.total_hrs ?? '',
          hsn_sac_code: row.hsn_sac_code ?? row.hsn ?? '',
          quantity: row.quantity ?? 1,
          rate: Number(row.rate) || 0,
        }))
      : [emptyLine()],
    otherCharges: String(sm.other_charges ?? inv.other_charges ?? 0),
    discount: String(sm.discount ?? inv.discount ?? 0),
    cgstPct: String(sm.cgst_percent ?? inv.cgst_percent ?? 9),
    sgstPct: String(sm.sgst_percent ?? inv.sgst_percent ?? 9),
    igstPct: String(sm.igst_percent ?? inv.igst_percent ?? 18),
    roundOff: sm.round_off != null ? String(sm.round_off) : inv.round_off != null ? String(inv.round_off) : '',
    bank: {
      account_holder_name: bk.account_holder_name ?? inv.account_holder_name ?? '',
      bank_name: bk.bank_name ?? inv.bank_name ?? '',
      branch_name: bk.branch_name ?? inv.branch_name ?? '',
      account_number: bk.account_number ?? inv.account_number ?? '',
      ifsc_code: bk.ifsc_code ?? inv.ifsc_code ?? '',
      upi_id: bk.upi_id ?? inv.upi_id ?? '',
    },
    signatory: {
      authorized_person_name: sg.authorized_person_name ?? inv.authorized_person_name ?? '',
      designation: sg.designation ?? inv.designation ?? '',
      signature: sg.signature ?? inv.signature_data ?? '',
    },
  };
}

export function buildPayload(state, totals, taxMode) {
  const items = state.lines
    .filter((l) => l.description.trim())
    .map((l) => {
      const quantity = Number(l.quantity);
      const rate = Number(l.rate);
      return {
        description: l.description.trim(),
        pl: l.pl === '' ? null : Number(l.pl),
        th: l.th === '' ? null : Number(l.th),
        hrs_set: l.hrs_set === '' ? null : Number(l.hrs_set),
        total_hrs: l.total_hrs === '' ? null : Number(l.total_hrs),
        hsn_sac_code: l.hsn_sac_code?.trim() || null,
        quantity,
        rate,
        amount: Math.round(quantity * rate * 100) / 100,
      };
    });

  return {
    firm_id: state.firmId,
    customer_details: state.customer,
    invoice_info: { ...state.info, date: state.info.invoice_date },
    items,
    summary: {
      total_job_work_amount: totals.total_job_work_amount,
      other_charges: totals.other_charges,
      discount: totals.discount,
      taxable_amount: totals.taxable_amount,
      cgst_percent: Number(state.cgstPct),
      sgst_percent: Number(state.sgstPct),
      igst_percent: Number(state.igstPct),
      cgst: totals.cgst,
      sgst: totals.sgst,
      igst: totals.igst,
      round_off: totals.roundOff,
      grand_total: totals.grandTotal,
      tax_mode: taxMode,
    },
    bank_details: state.bank,
    signatory: {
      authorized_person_name: state.signatory.authorized_person_name,
      designation: state.signatory.designation,
      signature: state.signatory.signature || null,
    },
  };
}

export { emptyLine };
