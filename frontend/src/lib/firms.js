/** @param {unknown} data */
export function normalizeFirms(data) {
  const list = Array.isArray(data) ? data : Array.isArray(data?.data) ? data.data : [];
  return list
    .map((f) => {
      const id = f?.id ?? f?._id;
      return id != null ? { ...f, id: String(id) } : null;
    })
    .filter(Boolean)
    .sort((a, b) => String(a.name).localeCompare(String(b.name)));
}
