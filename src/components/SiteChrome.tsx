import Link from "next/link";
import { config } from "@/lib/config";

export function SiteHeader() {
  return (
    <header className="border-b border-sand/70 bg-cream/80 backdrop-blur">
      <div className="mx-auto flex max-w-5xl items-center justify-between px-6 py-5">
        <Link href="/" className="flex flex-col leading-none">
          <span className="font-serif text-xl tracking-tight text-espresso">{config.studioName}</span>
          <span className="eyebrow mt-1">{config.tagline}</span>
        </Link>
        <nav className="flex items-center gap-2 text-sm">
          <Link href="/" className="btn-ghost">
            Sessions
          </Link>
          <a href={`mailto:${config.contactEmail}`} className="btn-secondary hidden sm:inline-flex">
            Get in touch
          </a>
        </nav>
      </div>
    </header>
  );
}

export function SiteFooter() {
  return (
    <footer className="mt-20 border-t border-sand/70">
      <div className="mx-auto flex max-w-5xl flex-col items-center gap-2 px-6 py-10 text-center text-sm text-espresso/60">
        <span className="font-serif text-base text-espresso">{config.studioName}</span>
        <span>
          Questions? <a className="underline hover:text-clay" href={`mailto:${config.contactEmail}`}>{config.contactEmail}</a>
        </span>
        <span className="text-xs">© {new Date().getFullYear()} {config.studioName}. All rights reserved.</span>
      </div>
    </footer>
  );
}
