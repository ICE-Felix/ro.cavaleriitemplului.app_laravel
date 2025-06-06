/// <reference lib="deno.ns" />
// Follow this setup guide to integrate the Deno language server with your editor:
// https://deno.land/manual/getting_started/setup_your_environment
// This enables autocomplete, go to definition, etc.

// Setup type definitions for built-in Supabase Runtime APIs
/// <reference types="https://esm.sh/@supabase/functions-js/src/edge-runtime.d.ts" />
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2';
import sgMail from "https://esm.sh/@sendgrid/mail";

// Cron job authorization key
const CRON_AUTH_KEY = 'c004e409-93a1-4073-ace3-2a49b0cb4345';

// Helper function to format date as DD-MM-YYYY
function formatDateRomanian(date: Date): string {
  const day = date.getDate().toString().padStart(2, '0');
  const month = (date.getMonth() + 1).toString().padStart(2, '0');
  const year = date.getFullYear();
  return `${day}-${month}-${year}`;
}

Deno.serve(async (req) => {
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
    
    // Parse request to determine mode: 'analyze' (default at 9 AM) or 'renew' (at 11 AM)
    let mode = 'analyze';
    try {
      const requestData = await req.json();
      if (requestData && requestData.mode === 'renew') {
        mode = 'renew';
      }
    } catch (e) {
      // If no JSON body or invalid JSON, default to 'analyze' mode
      console.log('No mode specified, defaulting to analyze mode');
    }
    
    console.log(`Rulare în modul: ${mode}`);
    console.log('Cheie cron validă detectată, se utilizează autentificarea cu rol de serviciu');
    
    // Initialize Supabase client with service role key
    const supabaseUrl = Deno.env.get('SUPABASE_URL') || '';
    const serviceRoleKey = Deno.env.get('SUPABASE_SERVICE_ROLE_KEY') || '';
    
    // Create client with service role to bypass RLS
    const supabase = createClient(supabaseUrl, serviceRoleKey);
    
    // Get yesterday's date for comparison
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    // We still need the ISO format for database queries
    const yesterdayISOStr = yesterday.toISOString().split('T')[0]; 
    // Romanian format for display
    const yesterdayRoStr = formatDateRomanian(yesterday);
    
    // Get today's date for email subject and potential subscription starting date
    const today = new Date();
    const todayISOStr = today.toISOString().split('T')[0];
    const todayRoStr = formatDateRomanian(today);
    
    // Find subscriptions that ended yesterday
    const { data: expiredSubscriptions, error: queryError } = await supabase
      .from('subscriptions')
      .select(`
        id, 
        contract_id,
        starting_date,
        ending_date,
        payment_status,
        invoice_status
      `)
      .eq('ending_date', yesterdayISOStr);
    
    if (queryError) {
      throw new Error(`Eroare la interogarea abonamentelor expirate: ${queryError.message}`);
    }
    
    // Variables to track status and messages
    let statusMessage = '';
    let subscriptionsWithStudentData = [];
    let renewalResults = [];
    
    // Check if we have any expired subscriptions
    if (!expiredSubscriptions || expiredSubscriptions.length === 0) {
      console.log('Nu s-au găsit abonamente expirate');
      statusMessage = 'Nu s-au găsit abonamente expirate pentru ziua de ieri';
    } else {
      // Process each subscription to check if there are future subscriptions
      const subscriptionsToProcess = [];
      
      for (const subscription of expiredSubscriptions) {
        if (!subscription.contract_id) {
          continue; // Skip subscriptions without contract_id
        }
        
        // Check if there's a future subscription for this contract
        const { data: futureSubscriptions, error: futureError } = await supabase
          .from('subscriptions')
          .select('id')
          .eq('contract_id', subscription.contract_id)
          .gt('starting_date', yesterdayISOStr)
          .limit(1);
          
        if (futureError) {
          console.error(`Eroare la verificarea abonamentelor viitoare: ${futureError.message}`);
          continue;
        }
        
        // Only include this subscription if there are no future subscriptions
        if (!futureSubscriptions || futureSubscriptions.length === 0) {
          subscriptionsToProcess.push(subscription);
        }
      }
      
      if (subscriptionsToProcess.length === 0) {
        console.log('Niciun abonament nu necesită procesare - toate au abonamente ulterioare');
        statusMessage = 'Toate abonamentele care au expirat ieri au deja abonamente urmatoare renoite';
      } else {
        console.log(`S-au găsit ${subscriptionsToProcess.length} abonamente fără continuări`);
        statusMessage = `S-au găsit ${subscriptionsToProcess.length} abonamente fără continuări care ar putea necesita reînnoire`;
        
        // Get student data for each subscription
        subscriptionsWithStudentData = await Promise.all(
          subscriptionsToProcess.map(async (subscription) => {
            if (!subscription.contract_id) {
              return {
                ...subscription,
                studentName: 'N/A',
                // Format dates for display
                startingDateFormatted: subscription.starting_date 
                  ? formatDateRomanian(new Date(subscription.starting_date)) 
                  : 'N/A',
                endingDateFormatted: subscription.ending_date 
                  ? formatDateRomanian(new Date(subscription.ending_date)) 
                  : 'N/A'
              };
            }
            
            // Get contract details
            const { data: contract } = await supabase
              .from('contracts')
              .select('id, student_id')
              .eq('id', subscription.contract_id)
              .single();
            
            if (!contract?.student_id) {
              return {
                ...subscription,
                studentName: 'N/A',
                startingDateFormatted: subscription.starting_date 
                  ? formatDateRomanian(new Date(subscription.starting_date)) 
                  : 'N/A',
                endingDateFormatted: subscription.ending_date 
                  ? formatDateRomanian(new Date(subscription.ending_date)) 
                  : 'N/A'
              };
            }
            
            // Get student details
            const { data: student } = await supabase
              .from('students')
              .select('first_name, last_name')
              .eq('id', contract.student_id)
              .single();
            
            return {
              ...subscription,
              studentName: student ? `${student.first_name} ${student.last_name}` : 'N/A',
              startingDateFormatted: subscription.starting_date 
                ? formatDateRomanian(new Date(subscription.starting_date)) 
                : 'N/A',
              endingDateFormatted: subscription.ending_date 
                ? formatDateRomanian(new Date(subscription.ending_date)) 
                : 'N/A'
            };
          })
        );
        
        // If in 'renew' mode, automatically renew subscriptions
        if (mode === 'renew') {
          console.log('Mod de reînnoire: se vor reînnoi abonamentele expirate');
          
          // Process each subscription for renewal
          for (const subscription of subscriptionsWithStudentData) {
            if (!subscription.contract_id) {
              renewalResults.push({
                subscription_id: subscription.id,
                status: 'error',
                message: 'Lipsă contract_id'
              });
              continue;
            }
            
            // Calculate tomorrow as starting date for new subscription
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const tomorrowISOStr = tomorrow.toISOString().split('T')[0];
            
            try {
              console.log(`Reînnoirea abonamentului: ${subscription.id} pentru contract: ${subscription.contract_id}`);
              
              // Call the create_subscription function to renew the subscription
              const response = await fetch(
                `${supabaseUrl}/functions/v1/create_subscription`,
                {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${serviceRoleKey}`
                  },
                  body: JSON.stringify({
                    contract_id: subscription.contract_id,
                    starting_date: todayISOStr // Use today as the starting date
                  })
                }
              );
              
              if (!response.ok) {
                const errorData = await response.text();
                console.error(`Eroare la reînnoirea abonamentului ${subscription.id}:`, errorData);
                renewalResults.push({
                  subscription_id: subscription.id,
                  status: 'error',
                  message: `Eroare: ${response.status} - ${errorData}`
                });
              } else {
                const renewalData = await response.json();
                console.log(`Abonament reînnoit cu succes: ${subscription.id}`);
                renewalResults.push({
                  subscription_id: subscription.id,
                  status: 'reînnoit',
                  data: renewalData
                });
              }
            } catch (error) {
              console.error(`Excepție la reînnoirea abonamentului ${subscription.id}:`, error);
              renewalResults.push({
                subscription_id: subscription.id,
                status: 'error',
                message: `Excepție: ${error.message}`
              });
            }
          }
          
          // Update status message to include renewal information
          const successfulRenewals = renewalResults.filter(result => result.status === 'reînnoit').length;
          statusMessage = `${statusMessage}. S-au reînnoit automat ${successfulRenewals} din ${subscriptionsWithStudentData.length} abonamente.`;
        }
      }
    }
    
    // Configure SendGrid API
    sgMail.setApiKey(Deno.env.get("SENDGRID_API_KEY") ?? "");
    
    // Email content will differ based on whether we have subscriptions to process
    let emailText, emailHtml, subscriptionDetails, htmlTable;
    
    if (subscriptionsWithStudentData.length > 0) {
      // Format the subscription data as text for email
      subscriptionDetails = subscriptionsWithStudentData.map(sub => {
        return `ID: ${sub.id}
ID Contract: ${sub.contract_id}
Student: ${sub.studentName}
Data Început: ${sub.startingDateFormatted}
Data Sfârșit: ${sub.endingDateFormatted}
Status Plată: ${sub.payment_status === 'payed' ? 'Plătit' : 'Neplătit'}
Status Factură: ${sub.invoice_status === 'generated' ? 'Generată' : 'Negenerată'}`;
      }).join('\n\n---\n\n');
      
      // Create HTML table for better formatting
      htmlTable = `
      <table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%;">
        <tr style="background-color: #f2f2f2;">
          <th>ID</th>
          <th>ID Contract</th>
          <th>Student</th>
          <th>Data Început</th>
          <th>Data Sfârșit</th>
          <th>Status Plată</th>
          <th>Status Factură</th>
        </tr>
        ${subscriptionsWithStudentData.map(sub => `
        <tr>
          <td>${sub.id}</td>
          <td>${sub.contract_id || 'N/A'}</td>
          <td>${sub.studentName}</td>
          <td>${sub.startingDateFormatted}</td>
          <td>${sub.endingDateFormatted}</td>
          <td>${sub.payment_status === 'payed' ? 'Plătit' : 'Neplătit'}</td>
          <td>${sub.invoice_status === 'generated' ? 'Generată' : 'Negenerată'}</td>
        </tr>`).join('')}
      </table>`;
      
      // Add renewal results if in renew mode
      let renewalResultsHtml = '';
      let renewalResultsText = '';
      
      if (mode === 'renew' && renewalResults.length > 0) {
        renewalResultsText = '\n\nRezultate reînnoire:\n\n' + 
          renewalResults.map(result => 
            `Abonament: ${result.subscription_id}\nStatus: ${result.status}\n${result.message || ''}`
          ).join('\n\n---\n\n');
        
        renewalResultsHtml = `
        <h3>Rezultate reînnoire</h3>
        <table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%;">
          <tr style="background-color: #f2f2f2;">
            <th>ID Abonament</th>
            <th>Status</th>
            <th>Mesaj</th>
          </tr>
          ${renewalResults.map(result => `
          <tr>
            <td>${result.subscription_id}</td>
            <td>${result.status}</td>
            <td>${result.message || ''}</td>
          </tr>`).join('')}
        </table>`;
      }
      
      emailText = `Abonamente EAEA care au expirat și urmează să fie reînnoite\n\n${statusMessage}\n\n${subscriptionDetails}${renewalResultsText}`;
      emailHtml = `<h2>Abonamente EAEA care au expirat și urmează să fie reînnoite</h2>
                  <p>${statusMessage}</p>
                  <p>Au fost identificate ${subscriptionsWithStudentData.length} abonamente expirate în data de ${yesterdayRoStr} care nu au un abonament viitor:</p>
                  ${htmlTable}
                  ${renewalResultsHtml}`;
    } else {
      // Simple email for when there are no subscriptions to process
      emailText = `Abonamente EAEA care au expirat și urmează să fie reînnoite\n\n${statusMessage}`;
      emailHtml = `<h2>Abonamente EAEA care au expirat și urmează să fie reînnoite</h2>
                  <p>${statusMessage}</p>
                  <p>Nu există abonamente care necesită procesare pentru data de ${yesterdayRoStr}.</p>`;
    }
    
    // Create a simple email
    const msg = {
      to: 'alex.bordei@eaea.ro',
      from: 'noreply@eaea.ro', // Use your verified sender
      cc: 'stefan.bordei@eaea.ro', // Add CC recipient
      subject: `Abonamente EAEA care au expirat și urmează să fie reînnoite (${todayRoStr}) - ${mode === 'renew' ? 'REÎNNOIRE AUTOMATĂ' : 'ANALIZĂ'}`,
      text: emailText,
      html: emailHtml,
    };
    
    // Send the email directly
    await sgMail.send(msg);
    
    console.log(`Notificare trimisă: ${statusMessage}`);
    
    return new Response(
      JSON.stringify({ 
        success: true, 
        mode: mode,
        message: `Notificare trimisă: ${statusMessage}`,
        renewalResults: mode === 'renew' ? renewalResults : []
      }),
      { headers: { "Content-Type": "application/json" } }
    );
  } catch (error) {
    console.error('Eroare în job-ul cron:', error.message);
    return new Response(
      JSON.stringify({ success: false, error: error.message }),
      { status: 500, headers: { "Content-Type": "application/json" } }
    );
  }
});
