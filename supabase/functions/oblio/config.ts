export const OBLIO_CLIENT_ID = Deno.env.get("OBLIO_CLIENT_ID") || "";
export const OBLIO_CLIENT_SECRET = Deno.env.get("OBLIO_CLIENT_SECRET") || "";

if (!OBLIO_CLIENT_ID || !OBLIO_CLIENT_SECRET) {
  console.warn("Missing Oblio API credentials in environment variables.");
}