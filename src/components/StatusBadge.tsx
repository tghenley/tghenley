import type { BookingStatus } from "@/lib/types";

const styles: Record<BookingStatus, string> = {
  pending: "bg-amber-100 text-amber-800",
  confirmed: "bg-sky-100 text-sky-800",
  paid: "bg-emerald-100 text-emerald-800",
  cancelled: "bg-rose-100 text-rose-700 line-through",
};

const labels: Record<BookingStatus, string> = {
  pending: "Pending",
  confirmed: "Confirmed",
  paid: "Paid",
  cancelled: "Cancelled",
};

export function StatusBadge({ status }: { status: BookingStatus }) {
  return <span className={`badge ${styles[status] ?? styles.pending}`}>{labels[status] ?? status}</span>;
}
