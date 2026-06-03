import { randomUUID } from "node:crypto";
import { getDb } from "./db";
import type {
  Booking,
  BookingStatus,
  BookingWithDetails,
  SessionEvent,
  Slot,
} from "./types";

// ------------------------------------------------------------------
// Slug helper
// ------------------------------------------------------------------

function slugify(input: string): string {
  return input
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "")
    .slice(0, 60);
}

function uniqueSlug(base: string): string {
  const db = getDb();
  let slug = base || "session";
  let n = 1;
  const stmt = db.prepare("SELECT 1 FROM events WHERE slug = ?");
  while (stmt.get(slug)) {
    n += 1;
    slug = `${base}-${n}`;
  }
  return slug;
}

// ------------------------------------------------------------------
// Events
// ------------------------------------------------------------------

export interface CreateEventInput {
  title: string;
  description: string;
  location: string;
  session_date: string;
  price_cents: number;
  deposit_cents: number;
  currency: string;
  slot_duration_min: number;
  published: boolean;
  // Slot generation
  start_time: string; // "HH:MM"
  end_time: string; // "HH:MM"
  break_min: number; // gap between slots
}

export function createEvent(input: CreateEventInput): SessionEvent {
  const db = getDb();
  const id = randomUUID();
  const slug = uniqueSlug(slugify(input.title));

  const insertEvent = db.prepare(`
    INSERT INTO events (id, slug, title, description, location, session_date,
      price_cents, deposit_cents, currency, slot_duration_min, published)
    VALUES (@id, @slug, @title, @description, @location, @session_date,
      @price_cents, @deposit_cents, @currency, @slot_duration_min, @published)
  `);

  const insertSlot = db.prepare(`
    INSERT INTO slots (id, event_id, start_time, end_time, status)
    VALUES (?, ?, ?, ?, 'open')
  `);

  const slots = generateSlots(
    input.session_date,
    input.start_time,
    input.end_time,
    input.slot_duration_min,
    input.break_min
  );

  const tx = db.transaction(() => {
    insertEvent.run({
      id,
      slug,
      title: input.title,
      description: input.description,
      location: input.location,
      session_date: input.session_date,
      price_cents: input.price_cents,
      deposit_cents: input.deposit_cents,
      currency: input.currency,
      slot_duration_min: input.slot_duration_min,
      published: input.published ? 1 : 0,
    });
    for (const s of slots) {
      insertSlot.run(randomUUID(), id, s.start, s.end);
    }
  });
  tx();

  return getEventById(id)!;
}

// Build a list of slot start/end ISO datetimes for a given day.
export function generateSlots(
  date: string,
  startHHMM: string,
  endHHMM: string,
  durationMin: number,
  breakMin: number
): { start: string; end: string }[] {
  const result: { start: string; end: string }[] = [];
  const start = new Date(`${date}T${startHHMM}:00`);
  const dayEnd = new Date(`${date}T${endHHMM}:00`);

  if (isNaN(start.getTime()) || isNaN(dayEnd.getTime()) || durationMin <= 0) {
    return result;
  }

  let cursor = new Date(start);
  // Guard against runaway loops.
  let guard = 0;
  while (guard < 500) {
    guard += 1;
    const slotEnd = new Date(cursor.getTime() + durationMin * 60_000);
    if (slotEnd > dayEnd) break;
    result.push({ start: cursor.toISOString(), end: slotEnd.toISOString() });
    cursor = new Date(slotEnd.getTime() + breakMin * 60_000);
  }
  return result;
}

export function listEvents(opts: { publishedOnly?: boolean; upcomingOnly?: boolean } = {}): SessionEvent[] {
  const db = getDb();
  const clauses: string[] = [];
  if (opts.publishedOnly) clauses.push("published = 1");
  if (opts.upcomingOnly) clauses.push("session_date >= date('now', '-1 day')");
  const where = clauses.length ? `WHERE ${clauses.join(" AND ")}` : "";
  return db
    .prepare(`SELECT * FROM events ${where} ORDER BY session_date ASC, created_at DESC`)
    .all() as SessionEvent[];
}

export function getEventById(id: string): SessionEvent | undefined {
  return getDb().prepare("SELECT * FROM events WHERE id = ?").get(id) as SessionEvent | undefined;
}

export function getEventBySlug(slug: string): SessionEvent | undefined {
  return getDb().prepare("SELECT * FROM events WHERE slug = ?").get(slug) as SessionEvent | undefined;
}

export function deleteEvent(id: string): void {
  getDb().prepare("DELETE FROM events WHERE id = ?").run(id);
}

export function setEventPublished(id: string, published: boolean): void {
  getDb().prepare("UPDATE events SET published = ? WHERE id = ?").run(published ? 1 : 0, id);
}

// ------------------------------------------------------------------
// Slots
// ------------------------------------------------------------------

export function listSlots(eventId: string): Slot[] {
  return getDb()
    .prepare("SELECT * FROM slots WHERE event_id = ? ORDER BY start_time ASC")
    .all(eventId) as Slot[];
}

export function getSlot(id: string): Slot | undefined {
  return getDb().prepare("SELECT * FROM slots WHERE id = ?").get(id) as Slot | undefined;
}

export function countSlots(eventId: string): { total: number; open: number } {
  const row = getDb()
    .prepare(
      `SELECT COUNT(*) AS total,
              SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open
       FROM slots WHERE event_id = ?`
    )
    .get(eventId) as { total: number; open: number | null };
  return { total: row.total, open: row.open ?? 0 };
}

// ------------------------------------------------------------------
// Bookings
// ------------------------------------------------------------------

export interface CreateBookingInput {
  slotId: string;
  client_name: string;
  client_email: string;
  client_phone: string;
  notes: string;
  amount_cents: number;
  payment_method: string;
}

export class SlotUnavailableError extends Error {
  constructor() {
    super("This time slot has already been booked.");
    this.name = "SlotUnavailableError";
  }
}

// Atomically reserve a slot and create the booking. Throws if the slot
// is no longer open (race-safe via the unique index on slot_id and the
// conditional UPDATE).
export function createBooking(input: CreateBookingInput): Booking {
  const db = getDb();
  const id = randomUUID();

  const tx = db.transaction(() => {
    const slot = getSlot(input.slotId);
    if (!slot) throw new SlotUnavailableError();

    const claimed = db
      .prepare("UPDATE slots SET status = 'booked' WHERE id = ? AND status = 'open'")
      .run(input.slotId);
    if (claimed.changes !== 1) throw new SlotUnavailableError();

    db.prepare(
      `INSERT INTO bookings (id, event_id, slot_id, client_name, client_email,
         client_phone, notes, status, amount_cents, payment_method)
       VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)`
    ).run(
      id,
      slot.event_id,
      input.slotId,
      input.client_name,
      input.client_email,
      input.client_phone,
      input.notes,
      input.amount_cents,
      input.payment_method
    );
  });
  tx();

  return getBooking(id)!;
}

export function getBooking(id: string): Booking | undefined {
  return getDb().prepare("SELECT * FROM bookings WHERE id = ?").get(id) as Booking | undefined;
}

export function getBookingWithDetails(id: string): BookingWithDetails | undefined {
  return getDb()
    .prepare(
      `SELECT b.*, e.title AS event_title, e.slug AS event_slug,
              s.start_time AS slot_start, s.end_time AS slot_end
       FROM bookings b
       JOIN events e ON e.id = b.event_id
       JOIN slots s ON s.id = b.slot_id
       WHERE b.id = ?`
    )
    .get(id) as BookingWithDetails | undefined;
}

export function listBookingsForEvent(eventId: string): BookingWithDetails[] {
  return getDb()
    .prepare(
      `SELECT b.*, e.title AS event_title, e.slug AS event_slug,
              s.start_time AS slot_start, s.end_time AS slot_end
       FROM bookings b
       JOIN events e ON e.id = b.event_id
       JOIN slots s ON s.id = b.slot_id
       WHERE b.event_id = ?
       ORDER BY s.start_time ASC`
    )
    .all(eventId) as BookingWithDetails[];
}

export function listAllBookings(): BookingWithDetails[] {
  return getDb()
    .prepare(
      `SELECT b.*, e.title AS event_title, e.slug AS event_slug,
              s.start_time AS slot_start, s.end_time AS slot_end
       FROM bookings b
       JOIN events e ON e.id = b.event_id
       JOIN slots s ON s.id = b.slot_id
       ORDER BY b.created_at DESC`
    )
    .all() as BookingWithDetails[];
}

export function setBookingPaymentLink(id: string, link: string): void {
  getDb().prepare("UPDATE bookings SET payment_link = ? WHERE id = ?").run(link, id);
}

export function updateBookingStatus(id: string, status: BookingStatus): void {
  const db = getDb();
  const booking = getBooking(id);
  if (!booking) return;

  const tx = db.transaction(() => {
    db.prepare("UPDATE bookings SET status = ? WHERE id = ?").run(status, id);
    // Cancelling a booking frees the slot back up.
    if (status === "cancelled") {
      db.prepare("UPDATE slots SET status = 'open' WHERE id = ?").run(booking.slot_id);
    } else {
      db.prepare("UPDATE slots SET status = 'booked' WHERE id = ?").run(booking.slot_id);
    }
  });
  tx();
}
