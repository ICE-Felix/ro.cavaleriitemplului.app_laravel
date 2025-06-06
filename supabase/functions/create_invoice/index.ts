import { serve } from "https://deno.land/std@0.140.0/http/server.ts";
import { OblioService } from "../oblio/oblio_service.ts";
import { OBLIO_CLIENT_ID, OBLIO_CLIENT_SECRET } from "../oblio/config.ts";

console.log("Oblio Service Supabase Function Initialized");

// Initialize OblioService
const oblioService = new OblioService();

serve(async (req) => {
  console.log("Received new request to Oblio Service Function");
  try {
    // Parse request body
    const { fetchProducts = false, fetchClients = false, invoiceData } =
      await req.json();

    const clientId = OBLIO_CLIENT_ID;
    const clientSecret = OBLIO_CLIENT_SECRET;

    console.log("Validating credentials");
    if (!clientId || !clientSecret) {
      console.warn("Missing clientId or clientSecret");
      return new Response(
        JSON.stringify({ error: "Missing clientId or clientSecret" }),
        { status: 400, headers: { "Content-Type": "application/json" } },
      );
    }

    // Fetch the Oblio authorization token
    console.log("Fetching Oblio authorization token");
    const token = await oblioService.getAuthToken(clientId, clientSecret);

    if (fetchProducts) {
      console.log("Fetching products from Oblio API");
      const products = await oblioService.getProducts(token);
      return new Response(
        JSON.stringify({ products }),
        { headers: { "Content-Type": "application/json" } },
      );
    }

    if (fetchClients) {
      console.log("Fetching clients from Oblio API");
      const clients = await oblioService.getClients(token);
      return new Response(
        JSON.stringify({ clients }),
        { headers: { "Content-Type": "application/json" } },
      );
    }

    if (!invoiceData) {
      console.warn("Missing invoice data");
      return new Response(
        JSON.stringify({ error: "Missing invoice data" }),
        { status: 400, headers: { "Content-Type": "application/json" } },
      );
    }

    // Validate invoice data structure
    if (
      !invoiceData.cif || !invoiceData.seriesName ||
      invoiceData.disableAutoSeries === undefined
    ) {
      return new Response(
        JSON.stringify({
          error:
            "Invalid invoice data: missing required fields (cif, seriesName, disableAutoSeries)",
        }),
        { status: 400, headers: { "Content-Type": "application/json" } },
      );
    }

    // Validate client data
    if (
      !invoiceData.client || !invoiceData.client.cif ||
      !invoiceData.client.name ||
      !invoiceData.client.address || !invoiceData.client.city ||
      !invoiceData.client.country
    ) {
      return new Response(
        JSON.stringify({
          error:
            "Invalid client data: missing required fields (cif, name, address, city, country)",
        }),
        { status: 400, headers: { "Content-Type": "application/json" } },
      );
    }

    // Validate products array
    if (
      !invoiceData.products || !Array.isArray(invoiceData.products) ||
      invoiceData.products.length === 0
    ) {
      return new Response(
        JSON.stringify({
          error: "Invalid products data: must be a non-empty array",
        }),
        { status: 400, headers: { "Content-Type": "application/json" } },
      );
    }

    // Validate each product
    for (const product of invoiceData.products) {
      if (
        !product.name || typeof product.price !== "number" ||
        typeof product.quantity !== "number"
      ) {
        return new Response(
          JSON.stringify({
            error:
              "Invalid product data: each product must have name, price, and quantity",
          }),
          { status: 400, headers: { "Content-Type": "application/json" } },
        );
      }
    }

    console.log("Creating invoice");
    const invoice = await oblioService.createInvoice(token, invoiceData);
    return new Response(
      JSON.stringify({ invoice }),
      { headers: { "Content-Type": "application/json" } },
    );

    console.log("Sending successful token response");
    return new Response(
      JSON.stringify({ token }),
      { headers: { "Content-Type": "application/json" } },
    );
  } catch (error) {
    console.error("Error in Oblio Service Function:", error);
    
    const errorMessage = error instanceof Error ? error.message : String(error);
    
    return new Response(
      JSON.stringify({ error: errorMessage }),
      { status: 500, headers: { "Content-Type": "application/json" } },
    );
  }
});
