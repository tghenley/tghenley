import type { Metadata } from "next";
import { config } from "@/lib/config";
import "./globals.css";

export const metadata: Metadata = {
  title: `${config.studioName} · ${config.tagline}`,
  description: `Book a mini session with ${config.studioName}.`,
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body className="min-h-screen font-sans">{children}</body>
    </html>
  );
}
