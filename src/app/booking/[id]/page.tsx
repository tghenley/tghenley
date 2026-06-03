import Link from "next/link";
import { notFound } from "next/navigation";
import { SiteFooter, SiteHeader } from "@/components/SiteChrome";
import { config, paymentMode } from "@/lib/config";
import { formatDateLong, formatMoney, formatTimeRange } from "@/lib/money";
import { getBookingWithDetails, getEventById } from "@/lib/repo";

export const dynamic = "force-dynamic";

export default function BookingConfirmationPage({
  params,
  searchParams,
}: {
  params: { id: string };
  searchParams: { paid?: string };
}) {
  const booking = getBookingWithDetails(params.id);
  if (!booking) notFound();

  const event = getEventById(booking.event_id)!;
  const mode = paymentMode();
  const justPaid = searchParams.paid === "1";
  const needsPayment = booking.amount_cents > 0 && booking.status !== "paid" && !justPaid;

  return (
    <div className="flex min-h-screen flex-col">
      <SiteHeader />

      <main className="mx-auto w-full max-w-xl flex-1 px-6 py-12">
        <div className="card overflow-hidden">
          <div className="bg-gradient-to-br from-sage/40 via-cream to-sand px-8 py-10 text-center">
            <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-espresso text-2xl text-cream">
              ✓
            </div>
            <h1 className="mt-4 font-serif text-3xl text-espresso">
              {justPaid ? "Payment received!" : "You're booked in"}
            </h1>
            <p className="mt-2 text-espresso/70">
              A confirmation will be sent to <strong>{booking.client_email}</strong>.
            </p>
          </div>

          <div className="space-y-4 p-8">
            <Row label="Session" value={booking.event_title} />
            <Row label="Date" value={formatDateLong(event.session_date)} />
            <Row label="Time" value={formatTimeRange(booking.slot_start, booking.slot_end)} />
            {event.location && <Row label="Location" value={event.location} />}
            <Row label="Name" value={booking.client_name} />
            {booking.amount_cents > 0 && (
              <Row
                label={event.deposit_cents > 0 ? "Deposit" : "Total"}
                value={formatMoney(booking.amount_cents, event.currency)}
              />
            )}

            {needsPayment ? (
              <div className="mt-6 rounded-xl bg-sand/50 p-5 text-center">
                {mode === "square" && booking.payment_link ? (
                  <>
                    <p className="text-sm text-espresso/80">
                      One last step — secure your spot by paying{" "}
                      {formatMoney(booking.amount_cents, event.currency)} via Square.
                    </p>
                    <a href={booking.payment_link} className="btn-primary mt-4 w-full">
                      Pay {formatMoney(booking.amount_cents, event.currency)} now
                    </a>
                    <p className="mt-2 text-xs text-espresso/50">
                      Payments are processed securely by Square.
                    </p>
                  </>
                ) : (
                  <p className="text-sm text-espresso/80">
                    We&rsquo;ll email an invoice for{" "}
                    {formatMoney(booking.amount_cents, event.currency)} to{" "}
                    <strong>{booking.client_email}</strong> shortly. Your spot is held in
                    the meantime.
                  </p>
                )}
              </div>
            ) : (
              booking.amount_cents > 0 && (
                <div className="mt-6 rounded-xl bg-emerald-50 p-5 text-center text-sm text-emerald-800">
                  Thank you! Your payment is all sorted. We can&rsquo;t wait to see you.
                </div>
              )
            )}

            <p className="pt-2 text-center text-sm text-espresso/60">
              Need to make a change? Email{" "}
              <a className="underline hover:text-clay" href={`mailto:${config.contactEmail}`}>
                {config.contactEmail}
              </a>
              .
            </p>
            <Link href="/" className="btn-secondary w-full">
              Back to all sessions
            </Link>
          </div>
        </div>
      </main>

      <SiteFooter />
    </div>
  );
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex items-baseline justify-between gap-4 border-b border-sand/60 pb-3 last:border-0">
      <span className="eyebrow">{label}</span>
      <span className="text-right text-espresso">{value}</span>
    </div>
  );
}
