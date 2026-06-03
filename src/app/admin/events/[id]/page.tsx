import Link from "next/link";
import { notFound, redirect } from "next/navigation";
import { StatusBadge } from "@/components/StatusBadge";
import { StatusControl } from "@/components/StatusControl";
import { deleteEventAction, togglePublishAction } from "@/lib/actions";
import { isAuthed } from "@/lib/auth";
import { config } from "@/lib/config";
import { formatDateLong, formatMoney, formatTimeRange } from "@/lib/money";
import { countSlots, getEventById, listBookingsForEvent } from "@/lib/repo";

export const dynamic = "force-dynamic";

export default function AdminEventPage({ params }: { params: { id: string } }) {
  if (!isAuthed()) redirect("/admin/login");

  const event = getEventById(params.id);
  if (!event) notFound();

  const bookings = listBookingsForEvent(event.id);
  const { open, total } = countSlots(event.id);
  const publicUrl = `${config.baseUrl}/sessions/${event.slug}`;

  return (
    <div>
      <Link href="/admin" className="text-sm text-espresso/60 hover:text-clay">← Dashboard</Link>

      <div className="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="font-serif text-3xl text-espresso">{event.title}</h1>
            {event.published ? (
              <span className="badge bg-emerald-100 text-emerald-800">Live</span>
            ) : (
              <span className="badge bg-sand text-espresso/70">Draft</span>
            )}
          </div>
          <p className="mt-1 text-espresso/60">
            {formatDateLong(event.session_date)}
            {event.location ? ` · ${event.location}` : ""}
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <form action={togglePublishAction}>
            <input type="hidden" name="id" value={event.id} />
            <input type="hidden" name="publish" value={event.published ? "0" : "1"} />
            <button className="btn-secondary" type="submit">
              {event.published ? "Unpublish" : "Publish"}
            </button>
          </form>
          <Link href={`/sessions/${event.slug}`} className="btn-secondary">View page ↗</Link>
        </div>
      </div>

      {/* Share link */}
      <div className="card mt-6 flex flex-wrap items-center justify-between gap-3 p-4">
        <div className="min-w-0">
          <p className="eyebrow">Public booking link</p>
          <p className="mt-0.5 truncate text-sm text-espresso">{publicUrl}</p>
        </div>
        <span className="text-sm text-espresso/60">
          {open} of {total} slots open
        </span>
      </div>

      {/* Bookings */}
      <h2 className="mt-10 font-serif text-2xl text-espresso">
        Bookings <span className="text-espresso/40">({bookings.length})</span>
      </h2>

      {bookings.length === 0 ? (
        <div className="card mt-4 p-10 text-center text-espresso/60">
          No bookings yet. Share your booking link to start filling slots.
        </div>
      ) : (
        <div className="card mt-4 overflow-x-auto">
          <table className="w-full min-w-[720px] text-left text-sm">
            <thead className="border-b border-sand text-espresso/60">
              <tr>
                <th className="px-4 py-3 font-medium">Time</th>
                <th className="px-4 py-3 font-medium">Client</th>
                <th className="px-4 py-3 font-medium">Contact</th>
                <th className="px-4 py-3 font-medium">Amount</th>
                <th className="px-4 py-3 font-medium">Status</th>
                <th className="px-4 py-3 font-medium">Set status</th>
              </tr>
            </thead>
            <tbody>
              {bookings.map((b) => (
                <tr key={b.id} className="border-b border-sand/50 last:border-0 align-top">
                  <td className="px-4 py-3 whitespace-nowrap text-espresso">
                    {formatTimeRange(b.slot_start, b.slot_end)}
                  </td>
                  <td className="px-4 py-3">
                    <div className="font-medium text-espresso">{b.client_name}</div>
                    {b.notes && <div className="mt-0.5 max-w-[220px] text-xs text-espresso/50">{b.notes}</div>}
                  </td>
                  <td className="px-4 py-3 text-espresso/70">
                    <a className="hover:text-clay" href={`mailto:${b.client_email}`}>{b.client_email}</a>
                    {b.client_phone && <div className="text-xs text-espresso/50">{b.client_phone}</div>}
                  </td>
                  <td className="px-4 py-3 whitespace-nowrap text-espresso">
                    {b.amount_cents > 0 ? formatMoney(b.amount_cents, event.currency) : "—"}
                    {b.payment_link && (
                      <a href={b.payment_link} target="_blank" rel="noreferrer" className="block text-xs text-clay underline">
                        Square link
                      </a>
                    )}
                  </td>
                  <td className="px-4 py-3"><StatusBadge status={b.status} /></td>
                  <td className="px-4 py-3"><StatusControl id={b.id} status={b.status} /></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Danger zone */}
      <div className="mt-12 flex items-center justify-between rounded-2xl border border-rose-200 bg-rose-50/50 p-5">
        <div>
          <p className="font-medium text-rose-800">Delete this session</p>
          <p className="text-sm text-rose-700/70">Removes the session and all its bookings. This can&rsquo;t be undone.</p>
        </div>
        <form action={deleteEventAction}>
          <input type="hidden" name="id" value={event.id} />
          <button type="submit" className="btn border border-rose-300 text-rose-700 hover:bg-rose-100">
            Delete
          </button>
        </form>
      </div>
    </div>
  );
}
