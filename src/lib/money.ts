// Formatting helpers for money and dates/times. Safe for both server
// and client components (no Node-only imports).

export function formatMoney(cents: number, currency: string): string {
  try {
    return new Intl.NumberFormat("en", {
      style: "currency",
      currency,
      currencyDisplay: "narrowSymbol",
    }).format(cents / 100);
  } catch {
    // Fallback if the currency code is unknown to Intl.
    return `${(cents / 100).toFixed(2)} ${currency}`;
  }
}

export function dollarsToCents(value: string | number): number {
  const n = typeof value === "number" ? value : parseFloat(value);
  if (isNaN(n) || n < 0) return 0;
  return Math.round(n * 100);
}

export function formatDateLong(isoDate: string): string {
  // isoDate may be "YYYY-MM-DD" or a full ISO datetime.
  const d = isoDate.length === 10 ? new Date(`${isoDate}T00:00:00`) : new Date(isoDate);
  if (isNaN(d.getTime())) return isoDate;
  return d.toLocaleDateString("en", {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
  });
}

export function formatTime(iso: string): string {
  const d = new Date(iso);
  if (isNaN(d.getTime())) return iso;
  return d.toLocaleTimeString("en", { hour: "numeric", minute: "2-digit" });
}

export function formatTimeRange(startIso: string, endIso: string): string {
  return `${formatTime(startIso)} – ${formatTime(endIso)}`;
}
