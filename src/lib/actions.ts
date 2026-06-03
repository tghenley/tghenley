"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { z } from "zod";
import { checkPassword, endSession, isAuthed, startSession } from "./auth";
import { config, paymentMode } from "./config";
import { dollarsToCents } from "./money";
import {
  createBooking,
  createEvent,
  deleteEvent,
  getBooking,
  getEventById,
  getEventBySlug,
  getSlot,
  setBookingPaymentLink,
  setEventPublished,
  SlotUnavailableError,
  updateBookingStatus,
} from "./repo";
import { createSquarePaymentLink } from "./square";
import type { BookingStatus } from "./types";

// ------------------------------------------------------------------
// Admin auth
// ------------------------------------------------------------------

export async function loginAction(_prev: unknown, formData: FormData): Promise<{ error?: string }> {
  const password = String(formData.get("password") || "");
  if (!checkPassword(password)) {
    return { error: "Incorrect password." };
  }
  startSession();
  redirect("/admin");
}

export async function logoutAction(): Promise<void> {
  endSession();
  redirect("/admin/login");
}

function requireAdmin(): void {
  if (!isAuthed()) redirect("/admin/login");
}

// ------------------------------------------------------------------
// Events (admin)
// ------------------------------------------------------------------

const eventSchema = z.object({
  title: z.string().min(2, "Title is required."),
  description: z.string().default(""),
  location: z.string().default(""),
  session_date: z.string().regex(/^\d{4}-\d{2}-\d{2}$/, "Pick a valid date."),
  price: z.string().default("0"),
  deposit: z.string().default("0"),
  currency: z.string().default(config.defaultCurrency),
  slot_duration_min: z.coerce.number().int().min(5).max(240),
  break_min: z.coerce.number().int().min(0).max(120).default(0),
  start_time: z.string().regex(/^\d{2}:\d{2}$/, "Pick a start time."),
  end_time: z.string().regex(/^\d{2}:\d{2}$/, "Pick an end time."),
  published: z.string().optional(),
});

export async function createEventAction(
  _prev: unknown,
  formData: FormData
): Promise<{ error?: string }> {
  requireAdmin();

  const parsed = eventSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) {
    return { error: parsed.error.errors[0]?.message || "Please check the form." };
  }
  const d = parsed.data;

  const priceCents = dollarsToCents(d.price);
  const depositCents = Math.min(dollarsToCents(d.deposit), priceCents || dollarsToCents(d.deposit));

  const event = createEvent({
    title: d.title,
    description: d.description,
    location: d.location,
    session_date: d.session_date,
    price_cents: priceCents,
    deposit_cents: depositCents,
    currency: d.currency.toUpperCase(),
    slot_duration_min: d.slot_duration_min,
    published: d.published === "on",
    start_time: d.start_time,
    end_time: d.end_time,
    break_min: d.break_min,
  });

  revalidatePath("/admin");
  revalidatePath("/");
  redirect(`/admin/events/${event.id}`);
}

export async function deleteEventAction(formData: FormData): Promise<void> {
  requireAdmin();
  const id = String(formData.get("id") || "");
  if (id) deleteEvent(id);
  revalidatePath("/admin");
  revalidatePath("/");
  redirect("/admin");
}

export async function togglePublishAction(formData: FormData): Promise<void> {
  requireAdmin();
  const id = String(formData.get("id") || "");
  const publish = String(formData.get("publish") || "") === "1";
  if (id) setEventPublished(id, publish);
  revalidatePath("/admin");
  revalidatePath("/");
}

export async function updateBookingStatusAction(formData: FormData): Promise<void> {
  requireAdmin();
  const id = String(formData.get("id") || "");
  const status = String(formData.get("status") || "") as BookingStatus;
  const valid: BookingStatus[] = ["pending", "confirmed", "paid", "cancelled"];
  if (id && valid.includes(status)) {
    updateBookingStatus(id, status);
  }
  revalidatePath("/admin");
}

// ------------------------------------------------------------------
// Booking (public)
// ------------------------------------------------------------------

const bookingSchema = z.object({
  slug: z.string(),
  slotId: z.string(),
  client_name: z.string().min(2, "Please enter your name."),
  client_email: z.string().email("Please enter a valid email."),
  client_phone: z.string().default(""),
  notes: z.string().default(""),
});

export async function createBookingAction(
  _prev: unknown,
  formData: FormData
): Promise<{ error?: string }> {
  const parsed = bookingSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) {
    return { error: parsed.error.errors[0]?.message || "Please check the form." };
  }
  const d = parsed.data;

  const event = getEventBySlug(d.slug);
  if (!event || !event.published) {
    return { error: "This session is no longer available." };
  }

  const slot = getSlot(d.slotId);
  if (!slot || slot.event_id !== event.id || slot.status !== "open") {
    return { error: "Sorry, that time has just been taken. Please choose another." };
  }

  // Amount to collect now: the deposit if set, otherwise the full price.
  const amountCents = event.deposit_cents > 0 ? event.deposit_cents : event.price_cents;
  const mode = paymentMode();

  let bookingId: string;
  try {
    const booking = createBooking({
      slotId: d.slotId,
      client_name: d.client_name,
      client_email: d.client_email,
      client_phone: d.client_phone,
      notes: d.notes,
      amount_cents: amountCents,
      payment_method: mode,
    });
    bookingId = booking.id;
  } catch (err) {
    if (err instanceof SlotUnavailableError) {
      return { error: "Sorry, that time has just been taken. Please choose another." };
    }
    throw err;
  }

  // If Square is configured and there's something to collect, generate a
  // hosted payment link the client can use on the confirmation page.
  if (mode === "square" && amountCents > 0) {
    const label = event.deposit_cents > 0 ? "Deposit" : "Payment";
    const result = await createSquarePaymentLink({
      bookingId,
      description: `${event.title} — ${label}`,
      amountCents,
      currency: event.currency,
      buyerEmail: d.client_email,
    });
    if (result.url) {
      setBookingPaymentLink(bookingId, result.url);
    }
  }

  revalidatePath(`/sessions/${event.slug}`);
  revalidatePath("/admin");
  redirect(`/booking/${bookingId}`);
}

// Re-generate / fetch a payment link on demand (used from the admin and
// confirmation page if the first attempt failed or Square was added later).
export async function regeneratePaymentLinkAction(formData: FormData): Promise<void> {
  const id = String(formData.get("id") || "");
  const booking = getBooking(id);
  if (!booking || booking.amount_cents <= 0) return;
  if (paymentMode() !== "square") return;

  const event = getEventById(booking.event_id);
  if (!event) return;

  const label = event.deposit_cents > 0 ? "Deposit" : "Payment";
  const result = await createSquarePaymentLink({
    bookingId: booking.id,
    description: `${event.title} — ${label}`,
    amountCents: booking.amount_cents,
    currency: event.currency,
    buyerEmail: booking.client_email,
  });
  if (result.url) setBookingPaymentLink(booking.id, result.url);
  revalidatePath(`/booking/${booking.id}`);
  revalidatePath("/admin");
}
