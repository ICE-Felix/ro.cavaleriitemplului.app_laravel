// Follow this setup guide to integrate the Deno language server with your editor:
// https://deno.land/manual/getting_started/setup_your_environment
// This enables autocomplete, go to definition, etc.

// Setup type definitions for built-in Supabase Runtime APIs
/// <reference types="https://esm.sh/v135/@supabase/functions-js@2.4.3/src/edge-runtime.d.ts" />

import { createClient } from "https://esm.sh/@supabase/supabase-js@2.46.1";


Deno.serve(async (req) => {
  //get request data
  const {
    number
  } = await req.json();

  try {
    validateContractData(
      number
    );
  } catch (error) {
    return new Response(
      JSON.stringify(error),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  const supabase = createClient(
    Deno.env.get("SUPABASE_URL") ?? "",
    Deno.env.get("SUPABASE_ANON_KEY") ?? "",
    {
      global: { headers: { Authorization: req.headers.get("Authorization")! } },
    },
  );


  return new Response(
    JSON.stringify("contract"),
    { headers: { "Content-Type": "application/json" } },
  );
});
  
//validate data
function validateContractData(
  number: number,
) {
  if (!number) {
    throw {
      error: "Missing required fields",
      status: 400,
    };
  }

  // Validate number is numeric
  if (isNaN(number)) {
    throw {
      error: "Number must be numeric",
      status: 400,
    };
  }
}
