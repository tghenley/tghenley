"use client";

import { useFormState, useFormStatus } from "react-dom";
import { useState } from "react";
import { createBookingAction } from "@/lib/actions";
import { formatTimeRange } from "@/lib/money";
import type { Slot } from "@/lib/types";

function SubmitButton({ disabled }: { disabled: boolean }) {
  const { pending } = useFormStatus();
  return (
    <button type="submit" className="btn-primary w-full" disabled={pending || disabled}>
      {pending ? "Reserving…" : "Reserve my spot"}
    </button>
  );
}

export function BookingForm({
  slug,
  slots,
  payNote,
}: {
  slug: string;
  slots: Slot[];
  payNote: string;
}) {
  const [state, formAction] = useFormState(createBookingAction, {});
  const [selected, setSelected] = useState<string>("");

  const openSlots = slots.filter((s) => s.status === "open");

  if (openSlots.length === 0) {
    return (
      <div className="card p-8 text-center">
        <p className="font-serif text-xl text-espresso">This session is fully booked</p>
        <p className="mt-2 text-sm text-espresso/70">
          Check back soon — cancellations do happen, and we add new dates often.
        </p>
      </div>
    );
  }

  return (
    <form action={formAction} className="card p-6 sm:p-8">
      <input type="hidden" name="slug" value={slug} />
      <input type="hidden" name="slotId" value={selected} />

      <h2 className="font-serif text-xl text-espresso">1. Choose a time</h2>
      <div className="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
        {openSlots.map((slot) => {
          const active = selected === slot.id;
          return (
            <button
              type="button"
              key={slot.id}
              onClick={() => setSelected(slot.id)}
              className={`rounded-lg border px-3 py-2.5 text-sm transition ${
                active
                  ? "border-espresso bg-espresso text-cream"
                  : "border-sand bg-white text-espresso hover:border-clay"
              }`}
            >
              {formatTimeRange(slot.start_time, slot.end_time)}
            </button>
          );
        })}
      </div>

      <h2 className="mt-8 font-serif text-xl text-espresso">2. Your details</h2>
      <div className="mt-4 grid gap-4">
        <div>
          <label className="label" htmlFor="client_name">Name</label>
          <input className="input" id="client_name" name="client_name" required placeholder="Your full name" />
        </div>
        <div className="grid gap-4 sm:grid-cols-2">
          <div>
            <label className="label" htmlFor="client_email">Email</label>
            <input className="input" id="client_email" name="client_email" type="email" required placeholder="you@email.com" />
          </div>
          <div>
            <label className="label" htmlFor="client_phone">Phone</label>
            <input className="input" id="client_phone" name="client_phone" placeholder="Optional" />
          </div>
        </div>
        <div>
          <label className="label" htmlFor="notes">Anything we should know?</label>
          <textarea className="input min-h-[80px]" id="notes" name="notes" placeholder="How many people, special requests, etc. (optional)" />
        </div>
      </div>

      <p className="mt-6 rounded-lg bg-sand/50 px-4 py-3 text-sm text-espresso/80">{payNote}</p>

      {state?.error && (
        <p className="mt-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">{state.error}</p>
      )}

      <div className="mt-6">
        <SubmitButton disabled={!selected} />
        {!selected && (
          <p className="mt-2 text-center text-xs text-espresso/50">Select a time above to continue.</p>
        )}
      </div>
    </form>
  );
}
