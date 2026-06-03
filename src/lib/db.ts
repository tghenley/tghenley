import Database from "better-sqlite3";
import fs from "node:fs";
import path from "node:path";

// ------------------------------------------------------------------
// SQLite connection (singleton across hot reloads in dev).
// ------------------------------------------------------------------

const DB_PATH = process.env.DATABASE_PATH || path.join(process.cwd(), "data", "henleys.db");

declare global {
  // eslint-disable-next-line no-var
  var __henleysDb: Database.Database | undefined;
}

function createDb(): Database.Database {
  const dir = path.dirname(DB_PATH);
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }

  const database = new Database(DB_PATH);
  database.pragma("journal_mode = WAL");
  database.pragma("foreign_keys = ON");
  migrate(database);
  return database;
}

function migrate(database: Database.Database): void {
  database.exec(`
    CREATE TABLE IF NOT EXISTS events (
      id                TEXT PRIMARY KEY,
      slug              TEXT NOT NULL UNIQUE,
      title             TEXT NOT NULL,
      description       TEXT NOT NULL DEFAULT '',
      location          TEXT NOT NULL DEFAULT '',
      session_date      TEXT NOT NULL,
      price_cents       INTEGER NOT NULL DEFAULT 0,
      deposit_cents     INTEGER NOT NULL DEFAULT 0,
      currency          TEXT NOT NULL DEFAULT 'AUD',
      slot_duration_min INTEGER NOT NULL DEFAULT 20,
      published         INTEGER NOT NULL DEFAULT 1,
      created_at        TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS slots (
      id          TEXT PRIMARY KEY,
      event_id    TEXT NOT NULL REFERENCES events(id) ON DELETE CASCADE,
      start_time  TEXT NOT NULL,
      end_time    TEXT NOT NULL,
      status      TEXT NOT NULL DEFAULT 'open'
    );
    CREATE INDEX IF NOT EXISTS idx_slots_event ON slots(event_id);

    CREATE TABLE IF NOT EXISTS bookings (
      id             TEXT PRIMARY KEY,
      event_id       TEXT NOT NULL REFERENCES events(id) ON DELETE CASCADE,
      slot_id        TEXT NOT NULL REFERENCES slots(id) ON DELETE CASCADE,
      client_name    TEXT NOT NULL,
      client_email   TEXT NOT NULL,
      client_phone   TEXT NOT NULL DEFAULT '',
      notes          TEXT NOT NULL DEFAULT '',
      status         TEXT NOT NULL DEFAULT 'pending',
      amount_cents   INTEGER NOT NULL DEFAULT 0,
      payment_method TEXT NOT NULL DEFAULT 'invoice',
      payment_link   TEXT,
      created_at     TEXT NOT NULL DEFAULT (datetime('now'))
    );
    CREATE INDEX IF NOT EXISTS idx_bookings_event ON bookings(event_id);
    CREATE UNIQUE INDEX IF NOT EXISTS idx_bookings_slot ON bookings(slot_id);
  `);
}

export function getDb(): Database.Database {
  if (!global.__henleysDb) {
    global.__henleysDb = createDb();
  }
  return global.__henleysDb;
}
