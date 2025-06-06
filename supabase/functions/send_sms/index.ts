// Follow this setup guide to integrate the Deno language server with your editor:
// https://deno.land/manual/getting_started/setup_your_environment
// This enables autocomplete, go to definition, etc.

// Setup type definitions for built-in Supabase Runtime APIs
/// <reference types="https://esm.sh/@supabase/functions-js/src/edge-runtime.d.ts" />

import { createClient } from "https://esm.sh/@supabase/supabase-js@2.46.1";

Deno.serve(async (req: Request) => {
  try {
    const { to, message, type = "transactional", sender = "4" } = await req.json();

    if (!to || typeof to !== "string") {
      return new Response("Invalid 'to' phone number.", { status: 400 });
    }

    if (!message || typeof message !== "string") {
      return new Response("Invalid 'message' body.", { status: 400 });
    }

    const supabase = createClient(
      Deno.env.get("SUPABASE_URL") ?? "",
      Deno.env.get("SUPABASE_ANON_KEY") ?? "",
      {
        global: { headers: { Authorization: req.headers.get("Authorization")! } },
      },
    );

    // Get SMSO API key from environment variables
    const SMSO_API_KEY = Deno.env.get("SMSO_API_KEY") ?? "";
    
    if (!SMSO_API_KEY) {
      console.error("SMSO_API_KEY environment variable not set");
      return new Response(
        JSON.stringify({ success: false, error: "SMS service configuration error" }),
        { status: 500, headers: { "Content-Type": "application/json" } }
      );
    }

    // Send SMS using SMSO API
    const response = await fetch("https://app.smso.ro/api/v1/send", {
      method: "POST",
      headers: {
        "X-Authorization": SMSO_API_KEY,
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        to: formatPhoneNumber(to),
        body: message,
        sender: sender,
        type: type,
      }),
    });

    const result = await response.json();

    if (response.ok) {
      console.log("SMS sent successfully", result);
      return new Response(JSON.stringify({ 
        success: true, 
        message: "SMS sent successfully",
        responseToken: result.responseToken,
        cost: result.transaction_cost 
      }), {
        headers: { "Content-Type": "application/json" },
      });
    } else {
      console.error("Error sending SMS:", result);
      return new Response(
        JSON.stringify({ success: false, error: "Failed to send SMS", details: result }),
        { status: 400, headers: { "Content-Type": "application/json" } }
      );
    }
  } catch (error) {
    console.error("Error:", error);
    return new Response(
      JSON.stringify({ success: false, error: "There was a problem sending the SMS" }),
      { status: 500, headers: { "Content-Type": "application/json" } }
    );
  }
});

// Helper function to format phone number to E.164 format
function formatPhoneNumber(phoneNumber: string): string {
  // Remove any non-digit characters
  const digitsOnly = phoneNumber.replace(/\D/g, "");
  
  // If it already starts with +, return as is
  if (phoneNumber.startsWith("+")) {
    return phoneNumber;
  }
  
  // If it starts with 0, assume Romanian number and replace with country code
  if (digitsOnly.startsWith("0")) {
    return "+4" + digitsOnly;
  }
  
  // Otherwise, add + prefix if missing
  return digitsOnly.startsWith("4") ? "+" + digitsOnly : "+40" + digitsOnly;
}

/* To invoke locally:

  1. Run `supabase start` (see: https://supabase.com/docs/reference/cli/supabase-start)
  2. Make an HTTP request:

  curl -i --location --request POST 'http://127.0.0.1:54321/functions/v1/send_sms' \
    --header 'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZS1kZW1vIiwicm9sZSI6ImFub24iLCJleHAiOjE5ODM4MTI5OTZ9.CRXP1A7WOeoJeXxjNni43kdQwgnWNReilDMblYTn_I0' \
    --header 'Content-Type: application/json' \
    --data '{"to":"0722334455", "message":"Hello from EAEA!"}'

*/
