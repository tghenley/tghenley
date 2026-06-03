# Henley Studio — Mini Sessions

Two ways to run mini-session booking for Henley Studio are included in this repo:

| Option | Where it lives | Best when |
| --- | --- | --- |
| **WordPress plugin** → [`wordpress-plugin/henley-studio-mini-sessions/`](wordpress-plugin/henley-studio-mini-sessions/) | Inside your existing WordPress site | You want booking native to your WP site, in your theme, managed from wp-admin, with no recurring fees. **(Recommended for the existing WordPress site.)** |
| **Standalone Next.js app** (below) | A separate deployable web app | You want a dedicated booking site/subdomain, or a starting point independent of WordPress. |

Both share the same model and flow: clients browse open sessions, pick a time
slot, and reserve; you manage everything privately; deposits are collected via
**Square** (hosted checkout — Square handles all card security), with an
**invoice fallback** when Square isn't configured. See the plugin's
[`readme.txt`](wordpress-plugin/henley-studio-mini-sessions/readme.txt) for install steps.

---

## Standalone Next.js app

A self-hosted booking app for running photography **mini sessions**, in the
spirit of [usesession.com](https://usesession.com) but tailored for the
Henley Studio brand and fully owned by you.

Clients browse your open session dates, pick an available time slot, and
reserve it in a few taps. You manage everything from a private admin area.
Payments are handled by **Square** (hosted checkout — Square takes care of all
card security), with a built-in fallback to **invoicing** (e.g. Xero) when
Square isn't configured.

---

## Features

**For your clients (public site)**
- A branded landing page listing all upcoming, published sessions
- A session page with details, pricing, live availability, and a booking form
- Pick-a-time slot selection that updates as spots fill up
- A confirmation page with a secure Square "Pay now" button (or an
  "we'll invoice you" message in invoice mode)

**For you (admin at `/admin`)**
- Password-protected dashboard with session, booking and revenue stats
- Create a session and auto-generate back-to-back time slots from a
  start/end time, slot length and break
- Set a full price and/or a deposit to collect at booking time
- Publish / unpublish sessions (drafts stay hidden)
- See every booking per session with client contact details and notes
- Change booking status (pending → confirmed → paid → cancelled);
  cancelling frees the slot back up
- Copy a shareable public booking link per session

**Payments**
- **Square mode** (when configured): each booking generates a hosted Square
  payment link for the deposit/total. Clients pay on Square's PCI-compliant
  page and are returned to your confirmation page.
- **Invoice mode** (default): bookings are recorded and you send an invoice
  yourself (e.g. via Xero). Mark them paid in the admin once settled.

---

## Tech stack

- **Next.js 14** (App Router, Server Actions) + **TypeScript**
- **SQLite** via `better-sqlite3` (a single file — zero external services)
- **Tailwind CSS** for styling
- **Zod** for input validation

---

## Quick start (local)

```bash
# 1. Install dependencies
npm install

# 2. Configure environment
cp .env.example .env.local
#    then edit .env.local — at minimum set ADMIN_PASSWORD and SESSION_SECRET

# 3. (Optional) load a couple of sample sessions to look around
npm run seed

# 4. Run it
npm run dev
```

- Public site: <http://localhost:3000>
- Admin: <http://localhost:3000/admin> (log in with `ADMIN_PASSWORD`)

For a production build locally: `npm run build && npm run start`.

---

## Configuration

All configuration is via environment variables — see **`.env.example`** for the
annotated list. The essentials:

| Variable | What it does |
| --- | --- |
| `ADMIN_PASSWORD` | Password for the `/admin` area. **Change this.** |
| `SESSION_SECRET` | Long random string used to sign the admin login cookie. |
| `NEXT_PUBLIC_STUDIO_NAME` | Studio name shown throughout (default: Henley Studio). |
| `NEXT_PUBLIC_TAGLINE` | Tagline under the name (default: Mini Sessions). |
| `NEXT_PUBLIC_CONTACT_EMAIL` | Contact email shown to clients. |
| `DEFAULT_CURRENCY` | Default currency for new sessions (e.g. `AUD`, `USD`). |
| `DATABASE_PATH` | Where the SQLite file lives (default `./data/henley-studio.db`). |
| `NEXT_PUBLIC_BASE_URL` | Public URL of the app (used for Square redirects). |

### Enabling Square payments

1. Create an app at <https://developer.squareup.com>.
2. Grab an **Access Token** and a **Location ID** (start in *Sandbox* to test).
3. Set in `.env.local`:
   ```bash
   SQUARE_ACCESS_TOKEN=your-token
   SQUARE_LOCATION_ID=your-location-id
   SQUARE_ENVIRONMENT=sandbox   # switch to "production" when live
   NEXT_PUBLIC_BASE_URL=https://your-deployed-url
   ```
4. Restart. The admin header will show **"Square on"**, and new bookings will
   generate a real hosted payment link.

If those aren't set, the app runs in **invoice mode** automatically — no code
changes needed.

> **Xero note:** the app doesn't auto-create Xero invoices (that needs a Xero
> OAuth connection). In invoice mode, the booking captures everything you need
> (client, session, amount) so you can raise the invoice in Xero and then mark
> the booking **Paid** in the admin.

---

## Deploying

Because the app stores data in a **SQLite file**, it needs a host with a
**persistent disk** (the file must survive restarts). Good options:

- **Railway**, **Render**, **Fly.io**, or any VPS / container host — mount a
  persistent volume and point `DATABASE_PATH` at it.

> ⚠️ **Vercel / Netlify caveat:** their default serverless filesystem is
> ephemeral and read-only, so SQLite data won't persist there. To deploy on
> Vercel, switch the data layer to a hosted database (e.g. Postgres on
> Supabase/Neon, or Turso/libSQL for SQLite-compatible hosting). The data
> access is isolated in `src/lib/db.ts` and `src/lib/repo.ts` to make that
> swap straightforward.

After deploying, set all the environment variables above (especially a strong
`ADMIN_PASSWORD`, a random `SESSION_SECRET`, and the correct
`NEXT_PUBLIC_BASE_URL`).

---

## Project structure

```
src/
  app/
    page.tsx                     # public landing — lists open sessions
    sessions/[slug]/page.tsx     # public session detail + booking form
    booking/[id]/page.tsx        # booking confirmation / pay page
    admin/
      login/page.tsx             # admin sign-in
      page.tsx                   # admin dashboard
      events/new/page.tsx        # create a session
      events/[id]/page.tsx       # manage a session + its bookings
  components/                    # UI (forms, chrome, badges)
  lib/
    db.ts                        # SQLite connection + schema/migrations
    repo.ts                      # all data access (events, slots, bookings)
    actions.ts                   # server actions (forms → repo → Square)
    square.ts                    # Square hosted payment link integration
    auth.ts                      # signed-cookie admin sessions
    money.ts                     # currency/date/time formatting
    config.ts / types.ts         # config + shared types
scripts/seed.cjs                 # optional sample data
```

---

## How it fits together

- **Booking is race-safe.** Reserving a slot uses a conditional `UPDATE` plus a
  unique index on `slot_id`, so two people can't grab the same time.
- **Slots auto-generate.** Creating a session builds slots from your start/end
  time, slot length and break — no manual slot entry.
- **Money is stored in cents** as integers to avoid floating-point rounding.

---

## A note on dependencies

This project pins **Next.js 14.2.x** (the latest patched release on that line).
`npm audit` may flag transitive advisories whose only fix is jumping to Next 16
(a breaking change that also requires React 19). Upgrading is straightforward
later but kept off the initial build for stability.
