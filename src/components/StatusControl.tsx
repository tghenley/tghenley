"use client";

import { useRef } from "react";
import { updateBookingStatusAction } from "@/lib/actions";
import type { BookingStatus } from "@/lib/types";

const OPTIONS: BookingStatus[] = ["pending", "confirmed", "paid", "cancelled"];

export function StatusControl({ id, status }: { id: string; status: BookingStatus }) {
  const formRef = useRef<HTMLFormElement>(null);

  return (
    <form ref={formRef} action={updateBookingStatusAction} className="inline">
      <input type="hidden" name="id" value={id} />
      <select
        name="status"
        defaultValue={status}
        onChange={() => formRef.current?.requestSubmit()}
        className="rounded-lg border border-sand bg-white px-2.5 py-1.5 text-xs text-espresso focus:border-clay focus:outline-none"
      >
        {OPTIONS.map((opt) => (
          <option key={opt} value={opt}>
            {opt.charAt(0).toUpperCase() + opt.slice(1)}
          </option>
        ))}
      </select>
    </form>
  );
}
