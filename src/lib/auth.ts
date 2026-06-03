import { createHmac, timingSafeEqual } from "node:crypto";
import { cookies } from "next/headers";

// Lightweight cookie-based admin session. We sign a fixed token with the
// SESSION_SECRET; presence of a valid signed cookie means "logged in".

const COOKIE_NAME = "henley_studio_admin";
const TOKEN_VALUE = "admin-ok";

function secret(): string {
  return process.env.SESSION_SECRET || "dev-insecure-secret-change-me";
}

function sign(value: string): string {
  return createHmac("sha256", secret()).update(value).digest("hex");
}

function makeCookie(): string {
  return `${TOKEN_VALUE}.${sign(TOKEN_VALUE)}`;
}

function verifyCookie(raw: string | undefined): boolean {
  if (!raw) return false;
  const [value, sig] = raw.split(".");
  if (value !== TOKEN_VALUE || !sig) return false;
  const expected = sign(TOKEN_VALUE);
  const a = Buffer.from(sig);
  const b = Buffer.from(expected);
  if (a.length !== b.length) return false;
  return timingSafeEqual(a, b);
}

export function checkPassword(password: string): boolean {
  const expected = process.env.ADMIN_PASSWORD || "change-me";
  const a = Buffer.from(password);
  const b = Buffer.from(expected);
  if (a.length !== b.length) return false;
  return timingSafeEqual(a, b);
}

export function startSession(): void {
  cookies().set(COOKIE_NAME, makeCookie(), {
    httpOnly: true,
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
    path: "/",
    maxAge: 60 * 60 * 24 * 7, // 7 days
  });
}

export function endSession(): void {
  cookies().delete(COOKIE_NAME);
}

export function isAuthed(): boolean {
  return verifyCookie(cookies().get(COOKIE_NAME)?.value);
}
