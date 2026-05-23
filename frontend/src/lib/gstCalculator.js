export function gstStateCode(gstin) {
  const s = String(gstin ?? '')
    .trim()
    .toUpperCase();
  if (s.length < 2) return null;
  return s.slice(0, 2);
}

export function detectTaxMode(sellerGstin, buyerGstin, buyerStateCode = null) {
  const seller = gstStateCode(sellerGstin);
  const buyer = buyerStateCode || gstStateCode(buyerGstin);
  if (!buyer || !seller) return 'intra_state';
  return seller === buyer ? 'intra_state' : 'inter_state';
}

export function lineAmount(row) {
  const qty = Number(row.quantity) || 0;
  const rate = Number(row.rate) || 0;
  return Math.round(qty * rate * 100) / 100;
}

export function lineSubtotal(lines) {
  return lines.reduce((sum, row) => sum + lineAmount(row), 0);
}

export function computeJobWorkSummary(
  lines,
  { otherCharges = 0, discount = 0, cgstPercent = 9, sgstPercent = 9, igstPercent = 18 },
  taxMode,
  roundOffOverride = null
) {
  const totalJobWork = lineSubtotal(lines);
  const other = Number(otherCharges) || 0;
  const disc = Number(discount) || 0;
  const taxable = Math.round(Math.max(0, totalJobWork + other - disc) * 100) / 100;

  const cgstPct = Number(cgstPercent) || 0;
  const sgstPct = Number(sgstPercent) || 0;
  const igstPct = Number(igstPercent) || 0;

  let cgst = 0;
  let sgst = 0;
  let igst = 0;

  if (taxMode === 'inter_state') {
    igst = Math.round(taxable * (igstPct / 100) * 100) / 100;
  } else {
    cgst = Math.round(taxable * (cgstPct / 100) * 100) / 100;
    sgst = Math.round(taxable * (sgstPct / 100) * 100) / 100;
  }

  const beforeRound = taxable + cgst + sgst + igst;
  const autoRoundOff = Math.round((Math.round(beforeRound) - beforeRound) * 100) / 100;
  const roundOff =
    roundOffOverride != null && roundOffOverride !== ''
      ? Number(roundOffOverride)
      : autoRoundOff;
  const grandTotal = Math.round((beforeRound + roundOff) * 100) / 100;

  return {
    total_job_work_amount: totalJobWork,
    other_charges: other,
    discount: disc,
    taxable_amount: taxable,
    cgst,
    sgst,
    igst,
    roundOff,
    grandTotal,
  };
}
