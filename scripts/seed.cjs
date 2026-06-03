/*
 * Seed the database with a couple of sample mini sessions so you can see
 * the app working immediately. Run with:  npm run seed
 *
 * Safe to run multiple times — it only seeds when there are no events yet.
 * This is a standalone script (it opens SQLite directly and ensures the
 * schema exists), so it can run before the app has ever started.
 */
const path = require("node:path");
const fs = require("node:fs");
const crypto = require("node:crypto");
const Database = require("better-sqlite3");

const DB_PATH = process.env.DATABASE_PATH || path.join(process.cwd(), "data", "henleys.db");
const CURRENCY = process.env.DEFAULT_CURRENCY || "AUD";

fs.mkdirSync(path.dirname(DB_PATH), { recursive: true });
const db = new Database(DB_PATH);
db.pragma("journal_mode = WAL");
db.pragma("foreign_keys = ON");

// Mirror of the schema in src/lib/db.ts (keep in sync).
db.exec(`
  CREATE TABLE IF NOT EXISTS events (
    id TEXT PRIMARY KEY, slug TEXT NOT NULL UNIQUE, title TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '', location TEXT NOT NULL DEFAULT '',
    session_date TEXT NOT NULL, price_cents INTEGER NOT NULL DEFAULT 0,
    deposit_cents INTEGER NOT NULL DEFAULT 0, currency TEXT NOT NULL DEFAULT 'AUD',
    slot_duration_min INTEGER NOT NULL DEFAULT 20, published INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
  );
  CREATE TABLE IF NOT EXISTS slots (
    id TEXT PRIMARY KEY, event_id TEXT NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    start_time TEXT NOT NULL, end_time TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'open'
  );
  CREATE INDEX IF NOT EXISTS idx_slots_event ON slots(event_id);
  CREATE TABLE IF NOT EXISTS bookings (
    id TEXT PRIMARY KEY, event_id TEXT NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    slot_id TEXT NOT NULL REFERENCES slots(id) ON DELETE CASCADE,
    client_name TEXT NOT NULL, client_email TEXT NOT NULL, client_phone TEXT NOT NULL DEFAULT '',
    notes TEXT NOT NULL DEFAULT '', status TEXT NOT NULL DEFAULT 'pending',
    amount_cents INTEGER NOT NULL DEFAULT 0, payment_method TEXT NOT NULL DEFAULT 'invoice',
    payment_link TEXT, created_at TEXT NOT NULL DEFAULT (datetime('now'))
  );
  CREATE INDEX IF NOT EXISTS idx_bookings_event ON bookings(event_id);
  CREATE UNIQUE INDEX IF NOT EXISTS idx_bookings_slot ON bookings(slot_id);
`);

if (db.prepare("SELECT COUNT(*) AS n FROM events").get().n > 0) {
  console.log("Database already has sessions — skipping seed.");
  process.exit(0);
}

function isoDateInDays(days) {
  const d = new Date();
  d.setDate(d.getDate() + days);
  return d.toISOString().slice(0, 10);
}

function generateSlots(date, startHHMM, endHHMM, durationMin, breakMin) {
  const out = [];
  let cursor = new Date(`${date}T${startHHMM}:00`);
  const dayEnd = new Date(`${date}T${endHHMM}:00`);
  let guard = 0;
  while (guard++ < 500) {
    const end = new Date(cursor.getTime() + durationMin * 60000);
    if (end > dayEnd) break;
    out.push({ start: cursor.toISOString(), end: end.toISOString() });
    cursor = new Date(end.getTime() + breakMin * 60000);
  }
  return out;
}

const insertEvent = db.prepare(`
  INSERT INTO events (id, slug, title, description, location, session_date,
    price_cents, deposit_cents, currency, slot_duration_min, published)
  VALUES (@id,@slug,@title,@description,@location,@session_date,
    @price_cents,@deposit_cents,@currency,@slot_duration_min,1)
`);
const insertSlot = db.prepare(
  "INSERT INTO slots (id, event_id, start_time, end_time, status) VALUES (?,?,?,?,'open')"
);

function seed(ev, times) {
  const id = crypto.randomUUID();
  insertEvent.run({ id, ...ev });
  for (const s of generateSlots(ev.session_date, times.start, times.end, ev.slot_duration_min, times.brk)) {
    insertSlot.run(crypto.randomUUID(), id, s.start, s.end);
  }
}

const tx = db.transaction(() => {
  seed(
    {
      slug: "spring-family-mini-sessions",
      title: "Spring Family Mini Sessions",
      description:
        "15-minute outdoor mini sessions in the golden afternoon light. Perfect for families, couples or fur-babies. You'll receive 5 edited digital images within two weeks.\n\nDress in soft, neutral tones and arrive 5 minutes early.",
      location: "Henley Beach foreshore",
      session_date: isoDateInDays(14),
      price_cents: 19500,
      deposit_cents: 5000,
      currency: CURRENCY,
      slot_duration_min: 15,
    },
    { start: "15:30", end: "17:30", brk: 5 }
  );

  seed(
    {
      slug: "christmas-studio-minis",
      title: "Christmas Studio Minis",
      description:
        "Cosy festive studio sessions with a styled Christmas set. 20 minutes, ideal for the whole family. Includes 8 edited images.",
      location: "The Studio · 12 Maker Lane",
      session_date: isoDateInDays(40),
      price_cents: 24500,
      deposit_cents: 7500,
      currency: CURRENCY,
      slot_duration_min: 20,
    },
    { start: "09:00", end: "13:00", brk: 10 }
  );
});
tx();

console.log("Seeded sample sessions ✓");
