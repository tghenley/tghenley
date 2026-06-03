import Link from "next/link";
import { redirect } from "next/navigation";
import { NewEventForm } from "@/components/NewEventForm";
import { isAuthed } from "@/lib/auth";
import { config } from "@/lib/config";

export const dynamic = "force-dynamic";

export default function NewEventPage() {
  if (!isAuthed()) redirect("/admin/login");

  return (
    <div className="mx-auto max-w-2xl">
      <Link href="/admin" className="text-sm text-espresso/60 hover:text-clay">← Dashboard</Link>
      <h1 className="mt-4 font-serif text-3xl text-espresso">New mini session</h1>
      <NewEventForm defaultCurrency={config.defaultCurrency} />
    </div>
  );
}
