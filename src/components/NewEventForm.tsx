"use client";

import { useFormState, useFormStatus } from "react-dom";
import { createEventAction } from "@/lib/actions";

function SubmitButton() {
  const { pending } = useFormStatus();
  return (
    <button type="submit" className="btn-primary" disabled={pending}>
      {pending ? "Creating…" : "Create session & generate slots"}
    </button>
  );
}

export function NewEventForm({ defaultCurrency }: { defaultCurrency: string }) {
  const [state, formAction] = useFormState(createEventAction, {});

  return (
    <form action={formAction} className="card mt-6 space-y-8 p-6 sm:p-8">
      <section>
        <h2 className="font-serif text-xl text-espresso">The session</h2>
        <div className="mt-4 grid gap-4">
          <div>
            <label className="label" htmlFor="title">Title</label>
            <input className="input" id="title" name="title" required placeholder="e.g. Spring Family Mini Sessions" />
          </div>
          <div>
            <label className="label" htmlFor="description">Description</label>
            <textarea
              className="input min-h-[110px]"
              id="description"
              name="description"
              placeholder="What's included, what to wear, the vibe… (shown on the booking page)"
            />
          </div>
          <div>
            <label className="label" htmlFor="location">Location</label>
            <input className="input" id="location" name="location" placeholder="e.g. Henley Beach, or the studio" />
          </div>
        </div>
      </section>

      <section>
        <h2 className="font-serif text-xl text-espresso">Date & times</h2>
        <p className="mt-1 text-sm text-espresso/60">
          We&rsquo;ll automatically create back-to-back slots between your start and end time.
        </p>
        <div className="mt-4 grid gap-4 sm:grid-cols-2">
          <div>
            <label className="label" htmlFor="session_date">Date</label>
            <input className="input" id="session_date" name="session_date" type="date" required />
          </div>
          <div>
            <label className="label" htmlFor="slot_duration_min">Slot length (minutes)</label>
            <input className="input" id="slot_duration_min" name="slot_duration_min" type="number" min={5} max={240} defaultValue={20} required />
          </div>
          <div>
            <label className="label" htmlFor="start_time">First slot starts</label>
            <input className="input" id="start_time" name="start_time" type="time" defaultValue="09:00" required />
          </div>
          <div>
            <label className="label" htmlFor="end_time">Last slot ends by</label>
            <input className="input" id="end_time" name="end_time" type="time" defaultValue="13:00" required />
          </div>
          <div>
            <label className="label" htmlFor="break_min">Gap between slots (minutes)</label>
            <input className="input" id="break_min" name="break_min" type="number" min={0} max={120} defaultValue={5} />
          </div>
        </div>
      </section>

      <section>
        <h2 className="font-serif text-xl text-espresso">Pricing</h2>
        <p className="mt-1 text-sm text-espresso/60">
          Set a deposit to collect part of the price at booking, or leave it at 0 to invoice/charge the full amount.
        </p>
        <div className="mt-4 grid gap-4 sm:grid-cols-3">
          <div>
            <label className="label" htmlFor="price">Full price</label>
            <input className="input" id="price" name="price" type="number" min={0} step="0.01" defaultValue={0} placeholder="0.00" />
          </div>
          <div>
            <label className="label" htmlFor="deposit">Deposit (collected now)</label>
            <input className="input" id="deposit" name="deposit" type="number" min={0} step="0.01" defaultValue={0} placeholder="0.00" />
          </div>
          <div>
            <label className="label" htmlFor="currency">Currency</label>
            <input className="input uppercase" id="currency" name="currency" maxLength={3} defaultValue={defaultCurrency} />
          </div>
        </div>
      </section>

      <label className="flex items-center gap-3 text-sm text-espresso">
        <input type="checkbox" name="published" defaultChecked className="h-4 w-4 rounded border-sand text-espresso focus:ring-clay" />
        Publish immediately (visible on your booking site)
      </label>

      {state?.error && (
        <p className="rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">{state.error}</p>
      )}

      <div className="flex justify-end">
        <SubmitButton />
      </div>
    </form>
  );
}
