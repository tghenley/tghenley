import Link from "next/link";
import { logoutAction } from "@/lib/actions";
import { isAuthed } from "@/lib/auth";
import { config, paymentMode, squareConfigured } from "@/lib/config";

export const dynamic = "force-dynamic";

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  // The login page lives under /admin too; when not signed in we render the
  // children bare (no nav). Protected pages redirect to /admin/login themselves.
  if (!isAuthed()) {
    return <>{children}</>;
  }

  return (
    <div className="min-h-screen bg-cream">
      <header className="border-b border-sand/70 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
          <div className="flex items-center gap-6">
            <Link href="/admin" className="flex flex-col leading-none">
              <span className="font-serif text-lg text-espresso">{config.studioName}</span>
              <span className="eyebrow mt-0.5">Admin</span>
            </Link>
            <nav className="hidden items-center gap-1 sm:flex">
              <Link href="/admin" className="btn-ghost">Dashboard</Link>
              <Link href="/admin/events/new" className="btn-ghost">New session</Link>
              <Link href="/" className="btn-ghost">View site ↗</Link>
            </nav>
          </div>
          <div className="flex items-center gap-3">
            <span
              className={`badge ${
                squareConfigured()
                  ? "bg-emerald-100 text-emerald-800"
                  : "bg-amber-100 text-amber-800"
              }`}
              title={
                squareConfigured()
                  ? "Square payment links are active."
                  : "Set SQUARE_ACCESS_TOKEN + SQUARE_LOCATION_ID to enable Square payment links."
              }
            >
              {paymentMode() === "square" ? "Square on" : "Invoice mode"}
            </span>
            <form action={logoutAction}>
              <button className="btn-ghost" type="submit">Sign out</button>
            </form>
          </div>
        </div>
      </header>
      <main className="mx-auto max-w-6xl px-6 py-8">{children}</main>
    </div>
  );
}
