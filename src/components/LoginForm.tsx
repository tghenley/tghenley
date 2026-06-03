"use client";

import { useFormState, useFormStatus } from "react-dom";
import { loginAction } from "@/lib/actions";

function SubmitButton() {
  const { pending } = useFormStatus();
  return (
    <button type="submit" className="btn-primary w-full" disabled={pending}>
      {pending ? "Signing in…" : "Sign in"}
    </button>
  );
}

export function LoginForm() {
  const [state, formAction] = useFormState(loginAction, {});
  return (
    <form action={formAction} className="card p-8">
      <label className="label" htmlFor="password">Studio password</label>
      <input
        className="input"
        id="password"
        name="password"
        type="password"
        required
        autoFocus
        placeholder="••••••••"
      />
      {state?.error && (
        <p className="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{state.error}</p>
      )}
      <div className="mt-5">
        <SubmitButton />
      </div>
    </form>
  );
}
