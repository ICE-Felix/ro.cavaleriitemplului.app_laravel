// Follow this setup guide to integrate the Deno language server with your editor:
// https://deno.land/manual/getting_started/setup_your_environment
// This enables autocomplete, go to definition, etc.

// Setup type definitions for built-in Supabase Runtime APIs
/// <reference types="https://esm.sh/v135/@supabase/functions-js@2.4.3/src/edge-runtime.d.ts" />
/// <reference lib="deno.ns" />

import { createClient } from 'https://esm.sh/@supabase/supabase-js@2';
import sgMail from "https://esm.sh/@sendgrid/mail";

// Cron job authorization key 
const CRON_AUTH_KEY = 'c004e409-93a1-4073-ace3-2a49b0cb4345';

// Helper function to format date as DD-MM-YYYY (for display)
function formatDateRomanian(date: Date): string {
  const day = date.getDate() < 10 ? `0${date.getDate()}` : `${date.getDate()}`;
  const month = date.getMonth() + 1 < 10 ? `0${date.getMonth() + 1}` : `${date.getMonth() + 1}`;
  const year = date.getFullYear();
  return `${day}-${month}-${year}`;
}

// Helper function to format date as YYYY-MM-DD (for database queries)
function formatDateISO(date: Date): string {
  return date.toISOString().split('T')[0];
}

// Function to send 


async function sendSMS(phoneNumber: string, message: string) {
  try {
    // Format phone number to ensure it starts with "40" and has no spaces
    const formattedPhone = phoneNumber.replace(/\s+/g, '');
    const finalPhone = formattedPhone.startsWith('0') ? '4' + formattedPhone : formattedPhone;
    
    console.log(`Attempting to send SMS to ${finalPhone}`);
    
    // Use the existing send_sms function
    const supabaseUrl = Deno.env.get('SUPABASE_URL') || '';
    const serviceRoleKey = Deno.env.get('SUPABASE_SERVICE_ROLE_KEY') || '';
    
    const response = await fetch(
      `${supabaseUrl}/functions/v1/send_sms`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${serviceRoleKey}`
        },
        body: JSON.stringify({
          to: finalPhone,
          message: message
        })
      }
    );
    
    if (!response.ok) {
      const errorData = await response.text();
      console.error(`SMS API error response:`, errorData);
      throw new Error(`SMS API responded with ${response.status}: ${errorData}`);
    }
    
    const result = await response.json();
    console.log(`SMS sent successfully to ${finalPhone}`);
    return { success: true, phoneNumber: finalPhone, message, ...result };
  } catch (error) {
    console.error(`Error sending SMS to ${phoneNumber}:`, error);
    const errorMessage = error instanceof Error ? error.message : String(error);
    return { success: false, error: errorMessage, phoneNumber };
  }
}

// Function to send email
async function sendEmail(to: string, subject: string, body: string) {
  try {
    // Configure SendGrid API
    sgMail.setApiKey(Deno.env.get("SENDGRID_API_KEY") ?? "");
    
    // Create a simple email with updated cc
    const msg = {
      to: to,
      from: 'noreply@eaea.ro',
      cc: ['contact@eaea.ro', 'alex.bordei@eaea.ro', 'stefan.bordei@eaea.ro'],
      subject: subject,
      text: body.replace(/<[^>]*>/g, ''), // Strip HTML for text version
      html: body,
    };
    
    try {
      // Send the email directly
      await sgMail.send(msg);
      return { success: true, to };
    } catch (sendError: any) {
      // If email fails, just log it and return failure
      console.error('Failed to send email:', sendError);
      throw sendError;
    }
  } catch (error) {
    console.error(`Error sending email to ${to}:`, error);
    const errorMessage = error instanceof Error ? error.message : String(error);
    return { success: false, error: errorMessage, to };
  }
}

// Define types for the event data structure
type EventWithSubscription = {
  id: string;
  date: string;
  starting_hour?: string;
  ending_hour?: string;
  event_no?: number;
  subscription_id: string;
  subscriptions?: {
    id: string;
    payment_status?: string;
    invoice_status?: string;
    contract_id?: string;
    invoice_id?: string;
    invoices?: {
      id: string;
      name?: string;
      file_url?: string;
    } | null;
  } | null;
};

// Admin report recipients (moved near the top of the file as a constant)
const ADMIN_EMAILS = [
  "alex.bordei@eaea.ro",
  "stefan.bordei@eaea.ro"
];

Deno.serve(async (req: Request) => {
  try {
    // Check for cron_key authorization
    const cronKeyHeader = req.headers.get('cron_key');
    const isValidCronKey = cronKeyHeader === CRON_AUTH_KEY;
    
    if (!isValidCronKey) {
      console.error('Cheie cron invalidă sau lipsă');
      return new Response(
        JSON.stringify({ 
          success: false, 
          error: 'Neautorizat. Credențiale invalide.' 
        }),
        { 
          status: 401, 
          headers: { "Content-Type": "application/json" } 
        }
      );
    }
    
    console.log('Cheie cron validă detectată, se utilizează autentificarea cu rol de serviciu');
    
    // Initialize Supabase client
    const supabaseUrl = Deno.env.get('SUPABASE_URL') || '';
    const serviceRoleKey = Deno.env.get('SUPABASE_SERVICE_ROLE_KEY') || '';
    const supabase = createClient(supabaseUrl, serviceRoleKey);
    
    const today = new Date();
    const formattedDate = formatDateISO(today);

    // First get today's events with unpaid subscriptions and fiscal sent invoices
    const { data: todayEvents, error: subscriptionsError } = await supabase
      .from('events')
      .select(`
        id,
        date,
        subscription_id,
        subscriptions:subscription_id (
          id,
          payment_status,
          invoice_status,
          contract_id,
          invoice_id,
          invoices (
            id,
            name,
            file_url
          )
        )
      `)
      .eq('date', formattedDate)
      .eq('subscriptions.payment_status', 'not_payed')
      .eq('subscriptions.invoice_status', 'fiscal_sent')
      .not('subscriptions.invoice_id', 'is', null);

    if (subscriptionsError) {
      throw new Error(`Eroare la interogarea abonamentelor: ${subscriptionsError.message}`);
    }

    // Get unique contract IDs from today's events
    const contractIds = [...new Set(todayEvents
      ?.filter(event => event.subscriptions?.contract_id)
      .map(event => event.subscriptions?.contract_id)
    )];

    // For each contract, get ALL unpaid subscriptions with fiscal sent invoices
    const allUnpaidSubscriptions = [];
    for (const contractId of contractIds) {
      const { data: contractSubscriptions, error: contractError } = await supabase
        .from('subscriptions')
        .select(`
          id,
          payment_status,
          invoice_status,
          contract_id,
          invoice_id,
          invoices (
            id,
            name,
            file_url
          )
        `)
        .eq('contract_id', contractId)
        .eq('payment_status', 'not_payed')
        .eq('invoice_status', 'fiscal_sent')
        .not('invoice_id', 'is', null);

      if (contractError) {
        console.error(`Error fetching subscriptions for contract ${contractId}:`, contractError);
        continue;
      }

      allUnpaidSubscriptions.push(...(contractSubscriptions || []));
    }

    // Filter out any duplicates and map to the format we need
    const validUnpaidSubscriptions = [...new Map(allUnpaidSubscriptions.map(item => [item.id, item])).values()]
      .map(subscription => ({
        ...subscription,
        event_date: todayEvents?.find(event => event.subscription_id === subscription.id)?.date
      }));

    console.log(`S-au găsit ${validUnpaidSubscriptions.length || 0} facturi restante pentru contactele cu evenimente azi`);

    // Create a map to group unpaid subscriptions by contact
    const contactSubscriptionsMap = new Map<string, {
      contact?: any,
      subscriptions: Array<{
        subscription: any,
        invoice: string,
        event_date: string
      }>
    }>();

    // Process each subscription and group by contact
    for (const subscription of (validUnpaidSubscriptions || [])) {
      if (!subscription.contract_id) {
        console.log(`Abonamentul ${subscription.id} nu are contract asociat, se ignoră`);
        continue;
      }

      // Get contract information
      const { data: contract, error: contractError } = await supabase
        .from('contracts')
        .select('id, contact_id')
        .eq('id', subscription.contract_id)
        .single();

      if (contractError || !contract) {
        console.error(`Eroare la obținerea contractului pentru abonamentul ${subscription.id}`);
        continue;
      }

      // Add to contact map
      if (!contactSubscriptionsMap.has(contract.contact_id)) {
        contactSubscriptionsMap.set(contract.contact_id, {
          subscriptions: []
        });
      }

      contactSubscriptionsMap.get(contract.contact_id)?.subscriptions.push({
        subscription,
        invoice: subscription.invoices?.name || 'factura',
        event_date: subscription.event_date
      });
    }

    // Results array to track notifications
    const smsResults = [];

    // Process each contact and their subscriptions
    for (const [contactId, data] of contactSubscriptionsMap) {
      // Get contact details
      const { data: contact, error: contactError } = await supabase
        .from('contacts')
        .select('id, first_name, last_name, phone_no')
        .eq('id', contactId)
        .single();

      if (contactError || !contact || !contact.phone_no) {
        console.error(`Eroare la obținerea contactului ID ${contactId}`);
        continue;
      }

      // Determine message type based on number of unpaid invoices
      const hasMultipleInvoices = data.subscriptions.length > 1;
      const invoicesList = data.subscriptions.map(s => s.invoice).join(', ');

      // Compose messages based on number of invoices
      let smsMessage, emailSubject, emailBody;

      if (hasMultipleInvoices) {
        smsMessage = `Pentru ${contact.first_name} ${contact.last_name} aveți ${data.subscriptions.length} facturi restante: ${invoicesList}. Puteți achita cu card, numerar la sediu sau prin transfer bancar. Pentru detalii, contactați-ne. Multumim! Early Alpha Engineering`;
        
        emailSubject = `Reamintire plată pentru ${contact.first_name} ${contact.last_name} la Early Alpha Engineering - Aveti ${data.subscriptions.length} facturi restante`;
        emailBody = `
          <p>Bună ziua,</p>
          <p>Vă reamintim că pentru ${contact.first_name} ${contact.last_name} aveți ${data.subscriptions.length} facturi restante:</p>
          <ul>
            ${data.subscriptions.map(s => `<li>Factura ${s.invoice}</li>`).join('')}
          </ul>
          <p>Puteți efectua plata prin următoarele modalități:</p>
          <ul>
            <li>Card bancar online</li>
            <li>Numerar la sediu</li>
            <li>Transfer bancar</li>
          </ul>
          <p>Pentru orice întrebări sau asistență, nu ezitați să ne contactați.</p>
          <p>Telefon: 0761161636</p>
          <p>Email: contact@eaea.ro</p>
          <p>Cu stimă,<br>Early Alpha Engineering</p>
        `;
      } else {
        const singleInvoice = data.subscriptions[0].invoice;
        smsMessage = `Pentru ${contact.first_name} ${contact.last_name}, nu s-a înregistrat inca o plata pentru factura ${singleInvoice}. Puteți achita cu card, numerar la sediu sau prin transfer bancar. Pentru detalii, contactați-ne. Multumim! Early Alpha Engineering`;
        
        emailSubject = `Reamintire plată pentru ${contact.first_name} ${contact.last_name} la Early Alpha Engineering - Aveti 1 factura restanta, ${singleInvoice}`;
        emailBody = `
          <p>Bună ziua,</p>
          <p>Vă reamintim că pentru ${contact.first_name} ${contact.last_name} nu s-a înregistrat inca o plata pentru factura ${singleInvoice}.</p>
          <p>Puteți efectua plata prin următoarele modalități:</p>
          <ul>
            <li>Card bancar online</li>
            <li>Numerar la sediu</li>
            <li>Transfer bancar</li>
          </ul>
          <p>Pentru orice întrebări sau asistență, nu ezitați să ne contactați.</p>
          <p>Telefon: 0761161636</p>
          <p>Email: contact@eaea.ro</p>
          <p>Cu stimă,<br>Early Alpha Engineering</p>
        `;
      }

      // Send notifications in parallel
      console.log(`Trimitere notificări către ${contact.first_name} ${contact.last_name}`);
      const [smsResult, emailResult] = await Promise.all([
        sendSMS(contact.phone_no, smsMessage),  // Use contact's actual phone number instead of hardcoded one
        sendEmail(contact.email || "contact@eaea.ro", emailSubject, emailBody)  // Use contact's email or fallback
      ]);

      // Track results
      smsResults.push({
        contact_name: `${contact.first_name} ${contact.last_name}`,
        phone: contact.phone_no,
        email: contact.email || 'N/A',
        invoices_count: data.subscriptions.length,
        invoices: invoicesList,
        sms_status: smsResult.success ? 'sent' : 'failed',
        email_status: emailResult.success ? 'sent' : 'failed',
        sms_error: smsResult.error || null,
        email_error: emailResult.error || null
      });
    }
    
    // After processing all subscriptions, send summary reports
    if (smsResults.length > 0) {
      const successCountSMS = smsResults.filter(result => result.sms_status === 'sent').length;
      const successCountEmail = smsResults.filter(result => result.email_status === 'sent').length;
      
      // Admin report recipients
      const subject = `Early Alpha Engineering - Raport Plăți Restante`;
      
      // Create detailed email body with HTML formatting
      let emailBody = `
        <h2>Raport Notificări Plăți Restante</h2>
        <p>Sumar notificări trimise:</p>
        <ul>
          <li>SMS: <strong>${successCountSMS}</strong> din <strong>${smsResults.length}</strong></li>
          <li>Email: <strong>${successCountEmail}</strong> din <strong>${smsResults.length}</strong></li>
        </ul>`;

      // Add table with details
      emailBody += `
        <table border="1" cellpadding="5" style="border-collapse: collapse;">
          <tr>
            <th>Contact</th>
            <th>Telefon</th>
            <th>Email</th>
            <th>Nr. facturi</th>
            <th>Facturi</th>
            <th>Status SMS</th>
            <th>Status Email</th>
            <th>Erori/Avertismente</th>
          </tr>`;
      
      smsResults.forEach(result => {
        emailBody += `
          <tr>
            <td>${result.contact_name}</td>
            <td>${result.phone}</td>
            <td>${result.email}</td>
            <td>${result.invoices_count}</td>
            <td>${result.invoices}</td>
            <td>${result.sms_status === 'sent' ? '✅ Trimis' : '❌ Eșuat'}</td>
            <td>${result.email_status === 'sent' ? '✅ Trimis' : '❌ Eșuat'}</td>
            <td>${[result.sms_error, result.email_error].filter(Boolean).join('<br>') || '-'}</td>
          </tr>`;
      });
      
      emailBody += `</table>`;
      
      // Send report to all admin recipients
      console.log(`Trimitere raport email către administratori`);
      await Promise.all(
        ADMIN_EMAILS.map(email => sendEmail(email, subject, emailBody))
      );
    }
    
    // Prepare response data
    const successCount = smsResults.filter(result => result.sms_status === 'sent').length;
    const statusMessage = `S-au trimis ${successCount} din ${smsResults.length} notificări SMS pentru plăți restante.`;
    
    console.log(statusMessage);
    
    return new Response(
      JSON.stringify({
        success: true,
        message: statusMessage,
        sms_results: smsResults
      }),
      { headers: { "Content-Type": "application/json" } }
    );
    
  } catch (error) {
    console.error('Eroare în job-ul cron de reamintire plăți:', error);
    const errorMessage = error instanceof Error ? error.message : String(error);
    return new Response(
      JSON.stringify({ success: false, error: errorMessage }),
      { status: 500, headers: { "Content-Type": "application/json" } }
    );
  }
});