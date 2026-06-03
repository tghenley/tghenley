import Link from "next/link";
import { SiteFooter, SiteHeader } from "@/components/SiteChrome";

export default function NotFound() {
  return (
    <div className="flex min-h-screen flex-col">
      <SiteHeader />
      <main className="mx-auto flex w-full max-w-xl flex-1 flex-col items-center justify-center px-6 py-20 text-center">
        <p className="eyebrow">404</p>
        <h1 className="mt-3 font-serif text-3xl text-espresso">We couldn&rsquo;t find that page</h1>
        <p className="mt-3 text-espresso/70">
          This session may have ended or the link may be incorrect.
        </p>
        <Link href="/" className="btn-primary mt-6">See current sessions</Link>
      </main>
      <SiteFooter />
    </div>
  );
}
