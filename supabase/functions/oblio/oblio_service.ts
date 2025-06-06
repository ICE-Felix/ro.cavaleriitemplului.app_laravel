export class OblioService {
    private baseUrl = "https://www.oblio.eu/api";
  
    /**
     * Fetches the authorization token from Oblio API.
     * @param clientId The client ID.
     * @param clientSecret The client secret.
     * @returns A promise that resolves to the authorization token.
     */
    async getAuthToken(clientId: string, clientSecret: string): Promise<string> {
        console.log(`Attempting to get auth token for client: ${clientId}`);
        const body = new URLSearchParams({
            client_id: clientId,
            client_secret: clientSecret,
        });
  
        console.log(`Making request to ${this.baseUrl}/authorize/token`);
        const response = await fetch(`${this.baseUrl}/authorize/token`, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: body.toString(),
        });
  
        console.log(`Received response with status: ${response.status}`);
        if (!response.ok) {
            console.error(`Token fetch failed with status: ${response.status}`);
            throw new Error(`Failed to fetch token. Status: ${response.status}`);
        }
  
        const data = await response.json();
        console.log("Successfully retrieved auth token");
        return data.access_token; // Adjust based on API response structure
    }
  
    /**
     * Fetches products using the Oblio API.
     * @param token The authorization token.
     * @returns A promise that resolves to the list of products.
     */
    async getProducts(token: string): Promise<any> {
        const cif = process.env.OBLIO_CIF || "";
        if (!cif) {
            console.error("Missing CIF in environment variables.");
            throw new Error("Missing CIF in environment variables.");
        }
  
        console.log(`Fetching products for CIF: ${cif}`);
        const url = `${this.baseUrl}/nomenclature/products?cif=${cif}`;
  
        const response = await fetch(url, {
            method: "GET",
            headers: {
                Authorization: `Bearer ${token}`,
                "Content-Type": "text/plain",
            },
        });
  
        console.log(`Received response with status: ${response.status}`);
        if (!response.ok) {
            console.error(`Failed to fetch products. Status: ${response.status}`);
            throw new Error(`Failed to fetch products. Status: ${response.status}`);
        }
  
        const data = await response.json();
        console.log("Successfully retrieved products");
        return data; // Adjust based on the API response structure
    }
  
    /**
     * Fetches clients using the Oblio API.
     * @param token The authorization token.
     * @returns A promise that resolves to the list of clients.
     */
    async getClients(token: string): Promise<any> {
        const cif = process.env.OBLIO_CIF || "";
        if (!cif) {
            console.error("Missing CIF in environment variables.");
            throw new Error("Missing CIF in environment variables.");
        }
  
        console.log(`Fetching clients for CIF: ${cif}`);
        const url = `${this.baseUrl}/nomenclature/clients?cif=${cif}`;
  
        const response = await fetch(url, {
            method: "GET",
            headers: {
                Authorization: `Bearer ${token}`,
                "Content-Type": "text/plain",
            },
        });
  
        console.log(`Received response with status: ${response.status}`);
        if (!response.ok) {
            console.error(`Failed to fetch clients. Status: ${response.status}`);
            throw new Error(`Failed to fetch clients. Status: ${response.status}`);
        }
  
        const data = await response.json();
        console.log("Successfully retrieved clients");
        return data; // Adjust based on the API response structure
    }
  
    /**
     * Validates the invoice data.
     * @param invoiceData The data for the invoice.
     */
    private validateinvoiceData(invoiceData: any): void {
        if (!invoiceData.cif || typeof invoiceData.cif !== "string") {
            throw new Error("Invalid CIF: must be a non-empty string.");
        }
  
        if (!invoiceData.seriesName || typeof invoiceData.seriesName !== "string") {
            throw new Error("Invalid seriesName: must be a non-empty string.");
        }
  
        if (typeof invoiceData.disableAutoSeries !== "number") {
            throw new Error("Invalid disableAutoSeries: must be a number.");
        }
  
        if (!invoiceData.client || typeof invoiceData.client !== "object") {
            throw new Error("Invalid client: must be an object.");
        }
  
        const requiredClientFields = ["cif", "name", "address", "city", "country"];
        for (const field of requiredClientFields) {
            if (!invoiceData.client[field]) {
                throw new Error(`Invalid client: missing required field '${field}'.`);
            }
        }
  
        if (!Array.isArray(invoiceData.products) || invoiceData.products.length === 0) {
            throw new Error("Invalid products: must be a non-empty array.");
        }
  
        for (const product of invoiceData.products) {
            if (!product.name || typeof product.name !== "string") {
                throw new Error("Invalid product: missing or invalid 'name'.");
            }
            if (!product.price || typeof product.price !== "number") {
                throw new Error("Invalid product: missing or invalid 'price'.");
            }
            if (!product.quantity || typeof product.quantity !== "number") {
                throw new Error("Invalid product: missing or invalid 'quantity'.");
            }
        }
  
        console.log("Invoice data validation passed.");
    }
  
    /**
     * Creates a invoice using the Oblio API.
     * @param token The authorization token.
     * @param invoiceData The data for the invoice.
     * @returns A promise that resolves to the created invoice.
     */
    async createInvoice(token: string, invoiceData: any): Promise<any> {
        console.log("Validating invoice data");
        this.validateinvoiceData(invoiceData);
  
        console.log("Creating invoice invoice");
        const url = `${this.baseUrl}/docs/invoice`;
  
        const response = await fetch(url, {
            method: "POST",
            headers: {
                Authorization: `Bearer ${token}`,
                "Content-Type": "application/json",
            },
            body: JSON.stringify(invoiceData),
        });
  
        console.log(`Received response with status: ${response.status}`);
  
        if (!response.ok) {
            console.error(`Failed to create invoice. Status: ${response.status}`);
            const errorData = await response.json();
            throw new Error(`Failed to create invoice: ${JSON.stringify(errorData)}`);
        }
  
        const data = await response.json();
        console.log("Successfully created invoice");
        return data;
    }}