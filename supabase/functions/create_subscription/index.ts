// Follow this setup guide to integrate the Deno language server with your editor:
// https://deno.land/manual/getting_started/setup_your_environment
// This enables autocomplete, go to definition, etc.

// Setup type definitions for built-in Supabase Runtime APIs
/// <reference types="https://esm.sh/v135/@supabase/functions-js@2.4.3/src/edge-runtime.d.ts" />

import { createClient } from "https://esm.sh/@supabase/supabase-js@2.46.1";
import { google } from "npm:googleapis";
import { validateContractData } from "./validate.ts";
import { writeFileSync } from 'node:fs';

Deno.serve(async (req) => {
  //Parse the request body to get contract_id and starting_date
  const {
    contract_id,
    starting_date,
  } = await req.json();

  //Log the received request data
  console.log("Request data:", { contract_id, starting_date });

  //Validate the contract data using helper function
  try {
    validateContractData(
      contract_id,
      starting_date,
    );
  } catch (error) {
    console.log("Validation error:", error);
    return new Response(
      JSON.stringify(error),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  //Initialize Supabase client with environment variables and authorization header
  const supabase = createClient(
    Deno.env.get("SUPABASE_URL") ?? "",
    Deno.env.get("SUPABASE_ANON_KEY") ?? "",
    {
      global: { headers: { Authorization: req.headers.get("Authorization")! } },
    },
  );

  //Query the database to get contract details by contract_id
  const { data: contract, error: contractError } = await supabase
    .from("contracts")
    .select("*")
    .eq("id", contract_id)
    .single();

  //Log the contract fetch results
  console.log("Contract fetch result:", { contract, contractError });

  //Handle contract fetch error
  if (contractError) {
    console.log("Contract fetch error:", contractError);
    return new Response(
      JSON.stringify({ error: "Error fetching contract", status: 500 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  //Handle case when contract is not found
  if (!contract) {
    console.log("Contract not found");
    return new Response(
      JSON.stringify({ error: "Contract not found", status: 404 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  //Validate that contract has a subscription type ID
  if (!contract.subscription_type_id) {
    console.log("Missing subscription type ID");
    return new Response(
      JSON.stringify({
        error: "Subscription type ID is required",
        status: 400,
      }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  // Fetch subscription type details from the database
  const { data: subscription_type, error: subscriptionTypeError } =
    await supabase
      .from("subscriptions_types")
      .select("*")
      .eq("id", contract.subscription_type_id)
      .single();

  // Log the subscription type fetch results
  console.log("Subscription type fetch result:", {
    subscription_type,
    subscriptionTypeError,
  });

  // Handle subscription type fetch error
  if (subscriptionTypeError) {
    console.log("Subscription type fetch error:", subscriptionTypeError);
    return new Response(
      JSON.stringify({ error: "Error fetching subscription", status: 500 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  // Handle case when subscription type is not found
  if (!subscription_type) {
    console.log("Subscription type not found");
    return new Response(
      JSON.stringify({ error: "Subscription not found", status: 404 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  // Initialize variables for timeslots and dates
  let timeslots;
  const timeslotDates = [];

  // Handle single timeslot subscription (4 sessions)
  if (subscription_type.sessions_no === 1) {
    console.log("Processing single timeslot subscription");
    // Fetch single timeslot details
    const { data: timeslot, error: timeslotError } = await supabase
      .from("timeslots")
      .select("*")
      .eq("id", contract.first_timeslot_id)
      .single();

    console.log("Timeslot fetch result:", { timeslot, timeslotError });

    // Handle timeslot fetch error
    if (timeslotError) {
      console.log("Timeslot fetch error:", timeslotError);
      return new Response(
        JSON.stringify({ error: "Error fetching timeslot", status: 500 }),
        { headers: { "Content-Type": "application/json" } },
      );
    }

    // Handle case when timeslot is not found
    if (!timeslot) {
      console.log("Timeslot not found");
      return new Response(
        JSON.stringify({ error: "Timeslot not found", status: 404 }),
        { headers: { "Content-Type": "application/json" } },
      );
    }
    // Calculate the first session date based on weekday
    const firstDate = new Date(starting_date);
    const daysUntilFirst = (timeslot.weekday_index - firstDate.getDay() + 7) %
      7;
    firstDate.setDate(firstDate.getDate() + daysUntilFirst);

    console.log("Calculated first date:", firstDate);

    // Generate dates for all 4 sessions
    for (let i = 0; i < 1; i++) {
      const currentDate = new Date(firstDate.toDateString());
      currentDate.setDate(currentDate.getDate() + (7 * i));

      timeslotDates.push({
        date: currentDate.toISOString().split("T")[0],
        starting_hour: timeslot.starting_hour,
        ending_hour: timeslot.ending_hour,
      });
    }
    console.log("Generated timeslot dates:", timeslotDates);
    timeslots = [timeslot];
  } else if (subscription_type.sessions_no === 4) {
    console.log("Processing single timeslot subscription");
    // Fetch single timeslot details
    const { data: timeslot, error: timeslotError } = await supabase
      .from("timeslots")
      .select("*")
      .eq("id", contract.first_timeslot_id)
      .single();

    console.log("Timeslot fetch result:", { timeslot, timeslotError });

    // Handle timeslot fetch error
    if (timeslotError) {
      console.log("Timeslot fetch error:", timeslotError);
      return new Response(
        JSON.stringify({ error: "Error fetching timeslot", status: 500 }),
        { headers: { "Content-Type": "application/json" } },
      );
    }

    // Handle case when timeslot is not found
    if (!timeslot) {
      console.log("Timeslot not found");
      return new Response(
        JSON.stringify({ error: "Timeslot not found", status: 404 }),
        { headers: { "Content-Type": "application/json" } },
      );
    }
    // Calculate the first session date based on weekday
    const firstDate = new Date(starting_date);
    const daysUntilFirst = (timeslot.weekday_index - firstDate.getDay() + 7) %
      7;
    firstDate.setDate(firstDate.getDate() + daysUntilFirst);

    console.log("Calculated first date:", firstDate);

    // Generate dates for all 4 sessions
    for (let i = 0; i < 4; i++) {
      const currentDate = new Date(firstDate.toDateString());
      currentDate.setDate(currentDate.getDate() + (7 * i));

      timeslotDates.push({
        date: currentDate.toISOString().split("T")[0],
        starting_hour: timeslot.starting_hour,
        ending_hour: timeslot.ending_hour,
      });
    }
    console.log("Generated timeslot dates:", timeslotDates);
    timeslots = [timeslot];
  } else {
    // Handle dual timeslot subscription (8 sessions)
    console.log("Processing dual timeslot subscription");
    // Fetch first timeslot details
    const { data: firstTimeslot, error: firstTimeslotError } = await supabase
      .from("timeslots")
      .select("*")
      .eq("id", contract.first_timeslot_id)
      .single();

    console.log("First timeslot fetch result:", {
      firstTimeslot,
      firstTimeslotError,
    });

    // Handle first timeslot fetch error
    if (firstTimeslotError) {
      console.log("First timeslot fetch error:", firstTimeslotError);
      return new Response(
        JSON.stringify({ error: "Error fetching first timeslot", status: 500 }),
        { headers: { "Content-Type": "application/json" } },
      );
    }

    // Handle case when first timeslot is not found
    if (!firstTimeslot) {
      console.log("First timeslot not found");
      return new Response(
        JSON.stringify({ error: "First timeslot not found", status: 404 }),
        { headers: { "Content-Type": "application/json" } },
      );
    }

    // Fetch second timeslot details
    const { data: secondTimeslot, error: secondTimeslotError } = await supabase
      .from("timeslots")
      .select("*")
      .eq("id", contract.second_timeslot_id)
      .single();

    console.log("Second timeslot fetch result:", {
      secondTimeslot,
      secondTimeslotError,
    });

    // Handle second timeslot fetch error
    if (secondTimeslotError) {
      console.log("Second timeslot fetch error:", secondTimeslotError);
      return new Response(
        JSON.stringify({
          error: "Error fetching second timeslot",
          status: 500,
        }),
        { headers: { "Content-Type": "application/json" } },
      );
    }

    // Handle case when second timeslot is not found
    if (!secondTimeslot) {
      console.log("Second timeslot not found");
      return new Response(
        JSON.stringify({ error: "Second timeslot not found", status: 404 }),
        { headers: { "Content-Type": "application/json" } },
      );
    }
    // Calculate the first session dates for both timeslots
    console.log("Starting date calculations");
    const firstDate1 = new Date(starting_date);
    const firstDate2 = new Date(starting_date);
    console.log("Initial dates:", { firstDate1, firstDate2 });

    // Calculate days until first occurrence for both timeslots
    const daysUntilFirst1 =
      (firstTimeslot.weekday_index - firstDate1.getDay() + 7) % 7;
    const daysUntilFirst2 =
      (secondTimeslot.weekday_index - firstDate2.getDay() + 7) % 7;
    console.log("Days until first occurrences:", {
      daysUntilFirst1,
      daysUntilFirst2,
    });

    // Adjust dates to first occurrence
    firstDate1.setDate(firstDate1.getDate() + daysUntilFirst1);
    firstDate2.setDate(firstDate2.getDate() + daysUntilFirst2);
    console.log("Adjusted first dates:", { firstDate1, firstDate2 });

    // Add 4 sessions for each timeslot, one week apart
    console.log("Starting to generate session dates");
    for (let i = 0; i < 4; i++) {
      console.log(`Generating dates for session ${i + 1}`);
      const currentDate1 = new Date(firstDate1.toDateString());
      const currentDate2 = new Date(firstDate2.toDateString());
      console.log("Initial dates for current session:", {
        currentDate1,
        currentDate2,
      });
      // Add 7 days multiplied by the iteration number to get the next session date
      currentDate1.setDate(currentDate1.getDate() + (7 * i));
      currentDate2.setDate(currentDate2.getDate() + (7 * i));
      console.log("Adjusted dates for current session:", {
        currentDate1,
        currentDate2,
      });

      // Add first timeslot session to the array
      timeslotDates.push({
        date: currentDate1.toISOString().split("T")[0],
        starting_hour: firstTimeslot.starting_hour,
        ending_hour: firstTimeslot.ending_hour,
      });

      // Add second timeslot session to the array
      timeslotDates.push({
        date: currentDate2.toISOString().split("T")[0],
        starting_hour: secondTimeslot.starting_hour,
        ending_hour: secondTimeslot.ending_hour,
      });
      console.log("Current timeslotDates:", timeslotDates);
    }

    // Store both timeslots in the timeslots array
    console.log("Final timeslots:", [firstTimeslot, secondTimeslot]);
    timeslots = [firstTimeslot, secondTimeslot];
  }

  // Calculate ending_date as the last date in timeslotDates
  const ending_date = timeslotDates[timeslotDates.length - 1].date;
  console.log("Calculated ending_date:", ending_date);

  // Insert subscription into database
  console.log("Inserting subscription");
  const { data: subscription, error: subscriptionError } = await supabase
    .from("subscriptions")
    .insert([
      {
        contract_id: contract.id,
        starting_date: timeslotDates[0].date,
        ending_date: ending_date,
      },
    ])
    .select()
    .single();
  console.log("Subscription insert result:", {
    subscription,
    subscriptionError,
  });

  // Handle subscription creation error
  if (subscriptionError) {
    console.error("Subscription creation error:", subscriptionError);
    return new Response(
      JSON.stringify({ error: "Error creating subscription", status: 500 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  // Create array of events to be inserted
  console.log("Preparing events to insert");
  const eventsToInsert = timeslotDates.map((timeslot, index) => ({
    subscription_id: subscription.id,
    date: timeslot.date,
    starting_hour: timeslot.starting_hour,
    ending_hour: timeslot.ending_hour,
    event_no: index + 1,
  }));
  console.log("Events to insert:", eventsToInsert);

  // Fetch student data using contract.student_id
  console.log("Fetching student data");
  const { data: student, error: studentError } = await supabase
    .from("students")
    .select("*")
    .eq("id", contract.student_id)
    .single();
  console.log("Student fetch result:", { student, studentError });

  // Handle student fetch error
  if (studentError) {
    console.error("Student fetch error:", studentError);
    return new Response(
      JSON.stringify({ error: "Error fetching student", status: 500 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  // Handle case when student is not found
  if (!student) {
    console.log("Student not found");
    return new Response(
      JSON.stringify({ error: "Student not found", status: 404 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  // Fetch contact data using contract.contact_id
  console.log("Fetching contact data");
  const { data: contact, error: contactError } = await supabase
    .from("contacts")
    .select("*")
    .eq("id", contract.contact_id)
    .single();
  console.log("Contact fetch result:", { contact, contactError });

  // Handle contact fetch error
  if (contactError) {
    console.error("Contact fetch error:", contactError);
    return new Response(
      JSON.stringify({ error: "Error fetching contact", status: 500 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  // Handle case when contact is not found
  if (!contact) {
    console.log("Contact not found");
    return new Response(
      JSON.stringify({ error: "Contact not found", status: 404 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }
  //create invoice
  console.log("Creating invoice");

  // Validate client data before making the request
  if (
    !contact.cnp || !contact.last_name || !contact.first_name ||
    !contact.address || !contact.city || !contact.country
  ) {
    console.error("Missing required client data");
    return new Response(
      JSON.stringify({
        error:
          "Missing required client data (CNP, name, address, city, country)",
        status: 400,
      }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  const response = await fetch(
    "https://wjfnbxnnuswxgfhdafny.supabase.co/functions/v1/create_invoice",
    {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Authorization": req.headers.get("Authorization")!,
      },
      body: JSON.stringify({
        invoiceData: {
          cif: "49100118",
          seriesName: "EA",
          disableAutoSeries: 0,
          language: "RO",
          issueDate: new Date().toISOString().split('T')[0], // Today's date in YYYY-MM-DD format
          dueDate: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 14 days from now
          currency: "RON",
          precision: 2,
          client: {
            cif: contact.cnp,
            name: `${contact.last_name} ${contact.first_name}`.trim(),
            address: contact.address,
            city: contact.city,
            country: contact.country,
          },
          products: [
            {
              name: subscription_type.weekend ? ("În weekend: " + subscription_type.name) : ("În timpul săptămânii: " + subscription_type.name),
              price: subscription_type.price,
              quantity: 1,
            },
          ],
        },
      }),
    },
  );
  const invoice = await response.json();
  const invoiceError = !response.ok;

  if (invoiceError) {
    console.error("Invoice creation error:", invoice);
    return new Response(
      JSON.stringify({ error: "Error creating invoice", status: 500 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }
// Download the invoice file
const fileResponse = await fetch(invoice.invoice.data.link);
const fileBlob = await fileResponse.blob(); // Get the Blob
const fileName = invoice.invoice.data.seriesName + invoice.invoice.data.number + ".pdf";
const fileBase64 = await blobToBase64(fileBlob);

  // Save the file as blob data
  const buffer = new Uint8Array(await fileBlob.arrayBuffer());
  console.log("Saving invoice to database");
  const { data: savedInvoice, error: savedInvoiceError } = await supabase
    .from("invoices")
    .insert({
      name: invoice.invoice.data.seriesName + invoice.invoice.data.number,
      file_url: invoice.invoice.data.link,
    })
    .select()
    .single();

  if (savedInvoiceError) {
    console.error("Invoice saving error:", savedInvoiceError);
    return new Response(
      JSON.stringify({ error: "Error saving invoice", status: 500 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  console.log("Updating subscription with invoice ID");
  const { error: subscriptionUpdateError } = await supabase
    .from("subscriptions")
    .update({
      invoice_id: savedInvoice.id,
      invoice_status: "fiscal_generated",
    })
    .eq("id", subscription.id);

  if (subscriptionUpdateError) {
    console.error("Subscription update error:", subscriptionUpdateError);
    return new Response(
      JSON.stringify({ error: "Error updating subscription", status: 500 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }
  console.log(timeslots);
  console.log(student);
  //Send email to user
  console.log("Sending email to user");
  const responseEmail = await fetch(
    "https://wjfnbxnnuswxgfhdafny.supabase.co/functions/v1/send_email",
    {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Authorization": req.headers.get("Authorization")!,
      },
      body: JSON.stringify({
        to: contact.email,
        template: "renewSubscription",
        data: {
          name: savedInvoice.name,
          invoiceUrl: savedInvoice.file_url,
          pdfFile: {
            blob: fileBase64, // Base64 string
            fileName, // File name
          },
          subscription: {
            id: subscription.id,
            starting_date: new Date(subscription.starting_date).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }),
            ending_date: new Date(subscription.ending_date).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }),
            sessions: eventsToInsert,
            student: student
          }
        },
      }),
    }
  );
  if (!responseEmail.ok) {
    console.error("Email sending error:", await responseEmail.json());
    return new Response(
      JSON.stringify({ error: "Error sending email", status: 500 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  // Update subscription invoice status to fiscal_sent
  console.log("Updating subscription invoice status to fiscal_sent");
  const { error: invoiceStatusError } = await supabase
    .from("subscriptions")
    .update({ invoice_status: "fiscal_sent" })
    .eq("id", subscription.id);

  if (invoiceStatusError) {
    console.error("Error updating invoice status:", invoiceStatusError);
    return new Response(
      JSON.stringify({ error: "Error updating invoice status", status: 500 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  // Send SMS notification if phone number is available
  if (contact.phone_no) {
    console.log("Sending SMS notification to:", contact.phone_no);
    
    // Format dates for SMS
    const formattedStartDate = new Date(subscription.starting_date).toLocaleDateString('ro-RO');
    const formattedEndDate = new Date(subscription.ending_date).toLocaleDateString('ro-RO');
    
    // Create a friendly message
    const smsMessage = `Salutare ${contact.first_name}, abonamentul pentru ${student.first_name} ${student.last_name} a fost reînnoit cu succes! Perioada: ${formattedStartDate} - ${formattedEndDate}. Detaliile complete au fost trimise pe email. Echipa Early Alpha Engineering`;
    
    try {
      const smsSendResponse = await fetch(
        "https://wjfnbxnnuswxgfhdafny.supabase.co/functions/v1/send_sms",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "Authorization": req.headers.get("Authorization")!,
          },
          body: JSON.stringify({
            to: contact.phone_no,
            message: smsMessage
          }),
        }
      );
      
      const smsResult = await smsSendResponse.json();
      if (smsSendResponse.ok) {
        console.log("SMS sent successfully:", smsResult);
      } else {
        console.error("SMS sending error:", smsResult);
        // Continue execution even if SMS fails
      }
    } catch (smsError) {
      console.error("Error while sending SMS:", smsError);
      // Continue execution even if SMS fails
    }
  } else {
    console.log("No phone number available for SMS notification");
  }

  // Load Google service account credentials
  console.log("Loading Google service account");
  const serviceAccount = JSON.parse(Deno.env.get("GOOGLE_SERVICE_ACCOUNT_KEY"));

  // Initialize Google Calendar authentication
  console.log("Initializing Google Calendar auth");
  const auth = new google.auth.GoogleAuth({
    credentials: serviceAccount,
    scopes: ["https://www.googleapis.com/auth/calendar"],
  });

  // Set up Google Calendar client and calendar ID
  const calendar = google.calendar({ version: "v3", auth });
  const calendarId =
    "c_fb997fd67006e6c368d82eb917e591dd013a816cfe6e181e0a4fae2c43e2a8b0@group.calendar.google.com";
  console.log("Calendar setup complete");

  // Initialize events array for storing created events
  const events = [];
  console.log("Starting event creation loop");
  for (const eventToInsert of eventsToInsert) {
    console.log("Creating event:", eventToInsert);
    // Insert event into database
    const { data: event, error: eventError } = await supabase
      .from("events")
      .insert(eventToInsert)
      .select()
      .single();
    console.log("Event creation result:", { event, eventError });

    // Handle event creation error
    if (eventError) {
      console.error("Event creation error:", eventError);
      return new Response(
        JSON.stringify({ error: "Error creating events", status: 500 }),
        { headers: { "Content-Type": "application/json" } },
      );
    }

    events.push(event);

    // Format dates for event summary
    console.log("Formatting dates");
    const formattedStartingDate = new Date(subscription.starting_date)
      .toLocaleDateString("ro-RO");
    const formattedEndingDate = new Date(subscription.ending_date)
      .toLocaleDateString("ro-RO");
    console.log("Formatted dates:", {
      formattedStartingDate,
      formattedEndingDate,
    });

    // Create event summary with special prefix for last event
    const isLastEvent = eventToInsert.event_no === eventsToInsert.length;
    const eventSummary = isLastEvent
      ? `U: ${student.last_name} ${student.first_name} ${formattedStartingDate} - ${formattedEndingDate}`
      : `${student.last_name} ${student.first_name} ${formattedStartingDate} - ${formattedEndingDate}`;
    console.log("Event summary:", eventSummary);

    // Prepare Google Calendar event object
    const gEvent = {
      summary: eventSummary,
      start: {
        dateTime: `${event.date}T${event.starting_hour}:00`,
        timeZone: "Europe/Bucharest",
      },
      end: {
        dateTime: `${event.date}T${event.ending_hour}:00`,
        timeZone: "Europe/Bucharest",
      },
    };
    console.log("Google Calendar event object:", gEvent);
    try {
      // Insert event into Google Calendar
      console.log("Attempting to insert event into Google Calendar");
      const response = await calendar.events.insert({
        calendarId: calendarId,
        resource: gEvent,
      });
      console.log(
        "Successfully inserted event into Google Calendar:",
        response.data,
      );

      // Update event with Google Calendar event ID
      console.log("Updating event with Google Event ID in Supabase");
      const { error: updateError } = await supabase
        .from("events")
        .update({ google_event_id: response.data.id })
        .eq("id", event.id);

      // Handle update error
      if (updateError) {
        console.error(
          "Error updating event with Google Event ID:",
          updateError,
        );
        return new Response(
          JSON.stringify({ error: "Error updating event", status: 500 }),
          { headers: { "Content-Type": "application/json" } },
        );
      }
      console.log("Successfully updated event with Google Event ID");
    } catch (error) {
      // Handle Google Calendar event creation error
      console.error("Error creating calendar event:", error);
      return new Response(
        JSON.stringify({ error: "Error creating calendar event", status: 500 }),
        { headers: { "Content-Type": "application/json" } },
      );
    }
  }

  // Return successful response with created events
  console.log("All events processed, returning response");
  return new Response(
    JSON.stringify(eventsToInsert),
    { headers: { "Content-Type": "application/json" } },
  );
});

async function blobToBase64(blob: Blob): Promise<string> {
  const arrayBuffer = await blob.arrayBuffer(); // Convert Blob to ArrayBuffer
  const uint8Array = new Uint8Array(arrayBuffer); // Convert ArrayBuffer to Uint8Array
  return btoa(String.fromCharCode(...uint8Array)); // Convert Uint8Array to Base64
}