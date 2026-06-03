// Centralised, environment-driven configuration.

export const config = {
  studioName: process.env.NEXT_PUBLIC_STUDIO_NAME || "Henley's Studio",
  tagline: process.env.NEXT_PUBLIC_STUDIO_TAGLINE || "Mini Sessions",
  contactEmail: process.env.NEXT_PUBLIC_CONTACT_EMAIL || "hello@henleys.studio",
  defaultCurrency: process.env.DEFAULT_CURRENCY || "AUD",
  baseUrl: process.env.NEXT_PUBLIC_BASE_URL || "http://localhost:3000",
};

// Square is "configured" only when we have both a token and a location id.
export function squareConfigured(): boolean {
  return Boolean(process.env.SQUARE_ACCESS_TOKEN && process.env.SQUARE_LOCATION_ID);
}

// Which payment flow the app presents to clients.
export type PaymentMode = "square" | "invoice";

export function paymentMode(): PaymentMode {
  return squareConfigured() ? "square" : "invoice";
}
