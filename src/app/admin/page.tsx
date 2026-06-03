import Link from "next/link";
import { redirect } from "next/navigation";
import { togglePublishAction } from "@/lib/actions";
import { isAuthed } from "@/lib/auth";
import { formatDateLong, formatMoney } from "@/lib/money";
import { countSlots, listAllBookings, listEvents } from "@/lib/repo";

export const dynamic = "force-dynamic";

export default function AdminDashboard() {
  if (!isAuthed()) redirect("/admin/login");

  const events = listEvents();
  const bookings = listAllBookings();
  const activeBookings = bookings.filter((b) => b.status !== "cancelled");
  const revenueCents = bookings
    .filter((b) => b.status === "paid")
    .reduce((sum, b) => sum + b.amount_cents, 0);
  const currency = events[0]?.currency || "AUD";

  return (
    <div>
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="font-serif text-3xl text-espresso">Dashboard</h1>
          <p className="mt-1 text-espresso/60">Your mini sessions at a glance.</p>
        </div>
        <Link href="/admin/events/new" className="btn-primary">+ New session</Link>
      </div>

      {/* Stats */}
      <div className="mt-6 grid gap-4 sm:grid-cols-3">
        <Stat label="Sessions" value={String(events.length)} />
        <Stat label="Bookings" value={String(activeBookings.length)} />
        <Stat label="Collected" value={formatMoney(revenueCents, currency)} />
      </div>

      {/* Events */}
      <h2 className="mt-10 font-serif text-2xl text-espresso">Sessions</h2>
      {events.length === 0 ? (
        <div className="card mt-4 p-10 text-center">
          <p className="text-espresso/70">No sessions yet.</p>
          <Link href="/admin/events/new" className="btn-primary mt-4">Create your first session</Link>
        </div>
      ) : (
        <div className="mt-4 grid gap-4">
          {events.map((event) => {
            const { open, total } = countSlots(event.id);
            const booked = total - open;
            return (
              <div key={event.id} className="card flex flex-wrap items-center gap-4 p-5">
                <div className="min-w-[200px] flex-1">
                  <div className="flex items-center gap-2">
                    <Link
                      href={`/admin/events/${event.id}`}
                      className="font-serif text-lg text-espresso hover:text-clay"
                    >
                      {event.title}
                    </Link>
                    {event.published ? (
                      <span className="badge bg-emerald-100 text-emerald-800">Live</span>
                    ) : (
                      <span className="badge bg-sand text-espresso/70">Draft</span>
                    )}
                  </div>
                  <p className="mt-0.5 text-sm text-espresso/60">
                    {formatDateLong(event.session_date)}
                    {event.location ? ` · ${event.location}` : ""}
                  </p>
                </div>

                <div className="text-sm text-espresso/70">
                  <span className="font-medium text-espresso">{booked}</span> / {total} booked
                </div>
                <div className="text-sm text-espresso/70">
                  {event.price_cents > 0 ? formatMoney(event.price_cents, event.currency) : "Free"}
                </div>

                <div className="flex items-center gap-2">
                  <form action={togglePublishAction}>
                    <input type="hidden" name="id" value={event.id} />
                    <input type="hidden" name="publish" value={event.published ? "0" : "1"} />
                    <button className="btn-ghost" type="submit">
                      {event.published ? "Unpublish" : "Publish"}
                    </button>
                  </form>
                  <Link href={`/admin/events/${event.id}`} className="btn-secondary">
                    Manage
                  </Link>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}

function Stat({ label, value }: { label: string; value: string }) {
  return (
    <div className="card p-5">
      <p className="eyebrow">{label}</p>
      <p className="mt-1 font-serif text-3xl text-espresso">{value}</p>
    </div>
  );
}
