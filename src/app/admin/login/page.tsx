import { redirect } from "next/navigation";
import { LoginForm } from "@/components/LoginForm";
import { config } from "@/lib/config";
import { isAuthed } from "@/lib/auth";

export const dynamic = "force-dynamic";

export default function AdminLoginPage() {
  if (isAuthed()) redirect("/admin");

  return (
    <div className="flex min-h-screen items-center justify-center px-6">
      <div className="w-full max-w-sm">
        <div className="mb-6 text-center">
          <p className="eyebrow">{config.studioName}</p>
          <h1 className="mt-2 font-serif text-3xl text-espresso">Studio admin</h1>
          <p className="mt-2 text-sm text-espresso/60">Sign in to manage your mini sessions.</p>
        </div>
        <LoginForm />
      </div>
    </div>
  );
}
