import Link from "next/link";
import { notFound } from "next/navigation";
import { BookingForm } from "@/components/BookingForm";
import { SiteFooter, SiteHeader } from "@/components/SiteChrome";
import { paymentMode } from "@/lib/config";
import { formatDateLong, formatMoney } from "@/lib/money";
import { countSlots, getEventBySlug, listSlots } from "@/lib/repo";

export const dynamic = "force-dynamic";

export default function SessionPage({ params }: { params: { slug: string } }) {
  const event = getEventBySlug(params.slug);
  if (!event || !event.published) notFound();

  const slots = listSlots(event.id);
  const { open, total } = countSlots(event.id);

  const collectCents = event.deposit_cents > 0 ? event.deposit_cents : event.price_cents;
  const mode = paymentMode();

  let payNote: string;
  if (collectCents <= 0) {
    payNote = "This session is free — just reserve your spot and we'll be in touch.";
  } else if (mode === "square") {
    const what = event.deposit_cents > 0 ? "a deposit" : "payment";
    payNote = `After you reserve, you'll be sent to a secure Square page to pay ${what} of ${formatMoney(
      collectCents,
      event.currency
    )} and lock in your time.`;
  } else {
    const what = event.deposit_cents > 0 ? "a deposit invoice" : "an invoice";
    payNote = `After you reserve, we'll email you ${what} for ${formatMoney(
      collectCents,
      event.currency
    )} to confirm your spot.`;
  }

  return (
    <div className="flex min-h-screen flex-col">
      <SiteHeader />

      <main className="mx-auto w-full max-w-5xl flex-1 px-6 py-10">
        <Link href="/" className="text-sm text-espresso/60 hover:text-clay">
          ← All sessions
        </Link>

        <div className="mt-6 grid gap-10 lg:grid-cols-[1fr_1.1fr]">
          {/* Details */}
          <div>
            <p className="eyebrow">{formatDateLong(event.session_date)}</p>
            <h1 className="mt-2 font-serif text-4xl text-espresso">{event.title}</h1>
            {event.location && <p className="mt-2 text-espresso/70">📍 {event.location}</p>}

            {event.description && (
              <p className="mt-6 whitespace-pre-line leading-relaxed text-espresso/80">
                {event.description}
              </p>
            )}

            <dl className="mt-8 grid grid-cols-2 gap-4">
              <div className="card p-4">
                <dt className="eyebrow">Session length</dt>
                <dd className="mt-1 font-serif text-2xl text-espresso">
                  {event.slot_duration_min} min
                </dd>
              </div>
              <div className="card p-4">
                <dt className="eyebrow">Price</dt>
                <dd className="mt-1 font-serif text-2xl text-espresso">
                  {event.price_cents > 0 ? formatMoney(event.price_cents, event.currency) : "Free"}
                </dd>
              </div>
              {event.deposit_cents > 0 && (
                <div className="card p-4">
                  <dt className="eyebrow">Deposit today</dt>
                  <dd className="mt-1 font-serif text-2xl text-espresso">
                    {formatMoney(event.deposit_cents, event.currency)}
                  </dd>
                </div>
              )}
              <div className="card p-4">
                <dt className="eyebrow">Availability</dt>
                <dd className="mt-1 font-serif text-2xl text-espresso">
                  {open} / {total}
                </dd>
              </div>
            </dl>
          </div>

          {/* Booking */}
          <div id="book">
            <BookingForm slug={event.slug} slots={slots} payNote={payNote} />
          </div>
        </div>
      </main>

      <SiteFooter />
    </div>
  );
}
