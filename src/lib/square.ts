import { randomUUID } from "node:crypto";
import { config, squareConfigured } from "./config";

// Minimal Square "Payment Links" integration using the REST API directly
// (no SDK dependency). Square hosts the checkout page and handles all card
// data / PCI compliance — we only ever receive a hosted URL back.
//
// Docs: https://developer.squareup.com/reference/square/checkout-api/create-payment-link

function squareBaseUrl(): string {
  const env = (process.env.SQUARE_ENVIRONMENT || "sandbox").toLowerCase();
  return env === "production"
    ? "https://connect.squareup.com"
    : "https://connect.squareupsandbox.com";
}

export interface PaymentLinkInput {
  bookingId: string;
  description: string; // e.g. "Spring Mini Session — Deposit"
  amountCents: number;
  currency: string;
  buyerEmail?: string;
}

export interface PaymentLinkResult {
  url: string | null;
  error?: string;
}

// Returns a hosted Square checkout URL for the given amount, or an error.
export async function createSquarePaymentLink(input: PaymentLinkInput): Promise<PaymentLinkResult> {
  if (!squareConfigured()) {
    return { url: null, error: "Square is not configured." };
  }
  if (input.amountCents <= 0) {
    return { url: null, error: "Nothing to charge." };
  }

  const body = {
    idempotency_key: randomUUID(),
    quick_pay: {
      name: input.description.slice(0, 255),
      price_money: {
        amount: input.amountCents,
        currency: input.currency,
      },
      location_id: process.env.SQUARE_LOCATION_ID,
    },
    checkout_options: {
      redirect_url: `${config.baseUrl}/booking/${input.bookingId}?paid=1`,
      ask_for_shipping_address: false,
    },
    pre_populated_data: input.buyerEmail ? { buyer_email: input.buyerEmail } : undefined,
  };

  try {
    const res = await fetch(`${squareBaseUrl()}/v2/online-checkout/payment-links`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Square-Version": "2024-10-17",
        Authorization: `Bearer ${process.env.SQUARE_ACCESS_TOKEN}`,
      },
      body: JSON.stringify(body),
      cache: "no-store",
    });

    const data = (await res.json()) as {
      payment_link?: { url?: string };
      errors?: { detail?: string }[];
    };

    if (!res.ok || !data.payment_link?.url) {
      const detail = data.errors?.[0]?.detail || `Square returned status ${res.status}`;
      return { url: null, error: detail };
    }

    return { url: data.payment_link.url };
  } catch (err) {
    return { url: null, error: err instanceof Error ? err.message : "Unknown error contacting Square." };
  }
}
