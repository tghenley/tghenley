import Link from "next/link";
import { SiteFooter, SiteHeader } from "@/components/SiteChrome";
import { config } from "@/lib/config";
import { formatDateLong, formatMoney } from "@/lib/money";
import { countSlots, listEvents } from "@/lib/repo";

export const dynamic = "force-dynamic";

export default function HomePage() {
  const events = listEvents({ publishedOnly: true, upcomingOnly: true });

  return (
    <div className="flex min-h-screen flex-col">
      <SiteHeader />

      {/* Hero */}
      <section className="mx-auto w-full max-w-5xl px-6 pt-16 pb-10 text-center">
        <p className="eyebrow">{config.tagline}</p>
        <h1 className="mt-3 font-serif text-4xl leading-tight text-espresso sm:text-5xl">
          Book your moment with {config.studioName}
        </h1>
        <p className="mx-auto mt-5 max-w-xl text-espresso/70">
          Short, beautiful photo sessions — pick a date, choose a time that suits you,
          and we&rsquo;ll take care of the rest. Spots are limited and go quickly.
        </p>
      </section>

      {/* Sessions */}
      <main className="mx-auto w-full max-w-5xl flex-1 px-6">
        {events.length === 0 ? (
          <div className="card mx-auto max-w-lg p-10 text-center">
            <h2 className="font-serif text-2xl text-espresso">No sessions open right now</h2>
            <p className="mt-3 text-espresso/70">
              New mini sessions are added regularly. Email us to be the first to know.
            </p>
            <a href={`mailto:${config.contactEmail}`} className="btn-primary mt-6">
              Join the list
            </a>
          </div>
        ) : (
          <div className="grid gap-6 sm:grid-cols-2">
            {events.map((event) => {
              const { open, total } = countSlots(event.id);
              const soldOut = open === 0;
              const fromPrice =
                event.deposit_cents > 0 ? event.deposit_cents : event.price_cents;
              return (
                <Link
                  key={event.id}
                  href={`/sessions/${event.slug}`}
                  className="card group flex flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-md"
                >
                  <div className="flex h-32 items-center justify-center bg-gradient-to-br from-sand via-cream to-sage/30">
                    <span className="font-serif text-3xl text-espresso/70">
                      {new Date(`${event.session_date}T00:00:00`).getDate()}
                    </span>
                  </div>
                  <div className="flex flex-1 flex-col p-6">
                    <p className="eyebrow">{formatDateLong(event.session_date)}</p>
                    <h3 className="mt-2 font-serif text-xl text-espresso">{event.title}</h3>
                    {event.location && (
                      <p className="mt-1 text-sm text-espresso/60">{event.location}</p>
                    )}
                    <p className="mt-3 line-clamp-2 text-sm text-espresso/70">{event.description}</p>
                    <div className="mt-5 flex items-center justify-between">
                      <span className="text-sm font-medium text-espresso">
                        {fromPrice > 0 ? (
                          <>
                            {event.deposit_cents > 0 ? "From " : ""}
                            {formatMoney(fromPrice, event.currency)}
                            {event.deposit_cents > 0 ? " deposit" : ""}
                          </>
                        ) : (
                          "Free"
                        )}
                      </span>
                      {soldOut ? (
                        <span className="badge bg-rose-100 text-rose-700">Sold out</span>
                      ) : (
                        <span className="badge bg-sage/30 text-espresso">
                          {open} of {total} left
                        </span>
                      )}
                    </div>
                  </div>
                </Link>
              );
            })}
          </div>
        )}
      </main>

      <SiteFooter />
    </div>
  );
}
