export interface SessionEvent {
  id: string;
  slug: string;
  title: string;
  description: string;
  location: string;
  session_date: string; // ISO date (YYYY-MM-DD)
  price_cents: number; // full price per session
  deposit_cents: number; // amount collected at booking (0 = pay full / invoice later)
  currency: string;
  slot_duration_min: number;
  published: number; // 0 | 1
  created_at: string;
}

export type SlotStatus = "open" | "held" | "booked";

export interface Slot {
  id: string;
  event_id: string;
  start_time: string; // ISO datetime
  end_time: string; // ISO datetime
  status: SlotStatus;
}

export type BookingStatus = "pending" | "confirmed" | "paid" | "cancelled";

export interface Booking {
  id: string;
  event_id: string;
  slot_id: string;
  client_name: string;
  client_email: string;
  client_phone: string;
  notes: string;
  status: BookingStatus;
  amount_cents: number;
  payment_method: string; // "square" | "invoice"
  payment_link: string | null;
  created_at: string;
}

// A slot joined with its event and (optional) booking — used in admin views.
export interface BookingWithDetails extends Booking {
  event_title: string;
  event_slug: string;
  slot_start: string;
  slot_end: string;
}
