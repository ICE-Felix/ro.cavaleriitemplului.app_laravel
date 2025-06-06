// Follow this setup guide to integrate the Deno language server with your editor:
// https://deno.land/manual/getting_started/setup_your_environment
// This enables autocomplete, go to definition, etc.

// Setup type definitions for built-in Supabase Runtime APIs
/// <reference types="https://esm.sh/v135/@supabase/functions-js@2.4.3/src/edge-runtime.d.ts" />

import { createClient } from "https://esm.sh/@supabase/supabase-js@2.46.1";

console.log("Hello from Functions!");
import * as fs from "node:fs";

import PizZip from "npm:pizzip";
import Docxtemplater from "npm:docxtemplater";

Deno.serve(async (req) => {
  //get request data
  const {
    number,
    date,
    contact_id,
    student_id,
    emergency_contact_id,
    subscription_type_id,
    first_timeslot_id,
    second_timeslot_id,
  } = await req.json();

  try {
    validateContractData(
      number,
      date,
      contact_id,
      student_id,
      emergency_contact_id,
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

  // Get contact data
  const { data: contact, error: contactError } = await supabase
    .from("contacts")
    .select("*")
    .eq("id", contact_id)
    .single();

  if (contactError) {
    return new Response(
      JSON.stringify({ error: "Error fetching contact", status: 500 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  if (!contact) {
    return new Response(
      JSON.stringify({ error: "Contact not found", status: 404 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  // Get contact data
  const { data: emergencyContact, error: emergencyContactError } =
    await supabase
      .from("contacts")
      .select("*")
      .eq("id", emergency_contact_id)
      .single();

  if (emergencyContactError) {
    return new Response(
      JSON.stringify({
        error: "Error fetching emergency contact",
        status: 500,
      }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  if (!emergencyContact) {
    return new Response(
      JSON.stringify({ error: "Emergency contact not found", status: 404 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  // Get student data
  const { data: student, error: studentError } = await supabase
    .from("students")
    .select("*")
    .eq("id", student_id)
    .single();

  if (studentError) {
    return new Response(
      JSON.stringify({ error: "Error fetching student", status: 500 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  if (!student) {
    return new Response(
      JSON.stringify({ error: "Student not found", status: 404 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  // Get subscription type data
  const { data: subscriptionTypes, error: subscriptionTypeError } =
    await supabase
      .from("subscriptions_types")
      .select("*");

  if (subscriptionTypeError) {
    return new Response(
      JSON.stringify({
        error: "Error fetching subscription type",
        status: 500,
      }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  if (!subscriptionTypes || subscriptionTypes.length === 0) {
    return new Response(
      JSON.stringify({ error: "Subscription type not found", status: 404 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  // Group by name and format
  const groupedByName = subscriptionTypes.reduce((acc, item) => {
    if (!acc[item.name]) {
      acc[item.name] = [];
    }
    acc[item.name].push(item);
    return acc;
  }, {});

  // Format the response
  let formattedResponse = Object.entries(groupedByName).map(
    ([name, subscriptions]) => {
      const formattedSubscriptions = (subscriptions as any[]).map((sub) => {
        let mark = false;
        if (subscription_type_id === sub.id) {
          mark = true;
        }

        const weekendLabel = sub.weekend
          ? "    [" + (mark === true ? "X" : "  ") + "] În weekend:"
          : "    [" + (mark === true ? "X" : "  ") + "] În timpul săptămânii:";
        const priceLabel = sub.name.includes("Family")
          ? `${sub.price} lei/cursant`
          : `${sub.price} lei`;
        return `    ${weekendLabel} ${priceLabel}`;
      }).join("\n");

      return `${name}:\n${formattedSubscriptions}`;
    },
  ).join("\n\n");

  // Helper function to calculate age
  function calculateAge(birthDate) {
    const [day, month, year] = birthDate.split("-").map(Number);
    const birth = new Date(year, month - 1, day);
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const monthDifference = today.getMonth() - birth.getMonth();

    // Adjust age if birth date hasn't occurred this year yet
    if (
      monthDifference < 0 ||
      (monthDifference === 0 && today.getDate() < birth.getDate())
    ) {
      age--;
    }
    return age;
  }

  // Load all timeslots
  const { data: allTimeslots, error: allTimeslotsError } = await supabase
    .from("timeslots")
    .select("*");

  if (allTimeslotsError) {
    return new Response(
      JSON.stringify({ error: "Error fetching all timeslots", status: 500 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  // Group timeslots by name
  const groupedTimeslots = allTimeslots.reduce((acc, item) => {
    if (!acc[item.day]) {
      acc[item.day] = [];
    }
    acc[item.day].push(item);
    return acc;
  }, {});

  // Format the timeslots response
  let formattedTimeslots = Object.entries(groupedTimeslots).map(
    ([name, slots]) => {
      const formattedSlots = (slots as any[]).map((slot) => {
        let mark = false;
        if (first_timeslot_id === slot.id || second_timeslot_id === slot.id) {
          mark = true;
        }
        const timeLabel = "[" + (mark === true ? "X" : " ") + "] " +
          `${slot.starting_hour}-${slot.ending_hour}`;
        return `    ${timeLabel}`;
      }).join("\n");

      return `${
        name.charAt(0).toUpperCase() + name.slice(1)
      }:\n${formattedSlots}`;
    },
  ).join("\n\n");

  // Load the DOCX template file
  const { data: templateData, error: templateError } = await supabase
    .storage
    .from("templates")
    .download("template.docx");

  if (templateError) {
    return new Response(
      JSON.stringify({ error: "Error fetching template", status: 500 }),
      { headers: { "Content-Type": "application/json" } },
    );
  }

  const content = await templateData.arrayBuffer();
  // Initialize pizzip and docxtemplater with the loaded content
  const zip = new PizZip(content);
  const doc = new Docxtemplater(zip, {
    paragraphLoop: true,
    linebreaks: true,
  });

  // Define the placeholders and their values
  let studentBirthDate;
  let studentAge;
  try {
    studentBirthDate = convertDateFormat(student.birth_date);
    studentAge = calculateAge(studentBirthDate);
  } catch (error) {
    console.error("Error processing student birth date:", error);
    studentBirthDate = "";
    studentAge = 0;
  }

  const data = {
    no: number,
    date: reverseDateFormat(date),
    contact_first_name: contact.first_name,
    contact_last_name: contact.last_name,
    contact_address: contact.address,
    contact_phone: contact.phone_no,
    contact_email: contact.email,
    contact_id_series: contact.id_series,
    contact_id_no: contact.id_no,
    contact_personal_no: contact.cnp ? contact.cnp : contact.cui,
    student_first_name: student.first_name,
    student_last_name: student.last_name,
    student_birth_date: studentBirthDate,
    if_14_student_first_name: studentAge >= 14 ? student.first_name : "",
    if_14_student_last_name: studentAge >= 14 ? student.last_name : "",
    if_14_date: studentAge >= 14 ? studentBirthDate : "",
    contact_emergency_first_name: emergencyContact.first_name,
    contact_emergency_last_name: emergencyContact.last_name,
    contact_emergency_phone_no: emergencyContact.phone_no,
    subscriptions_types: "\n" + formattedResponse,
    timeslots: "\n" + formattedTimeslots,
  };


  
  // Replace placeholders in the document
  doc.setData(data);

  try {
    // Render the document with the new data
    doc.render();
  } catch (error) {
    console.error("Error rendering document:", error);
    throw error;
  }

  // Create a new DOCX file with the replaced data
  const buffer = doc.getZip().generate({ type: "nodebuffer" });
  // Format the filename as "Contract EAEA {no} {date} - {contact_first_name} {contact_last_name}.docx"
  const formattedFileName =
    `Contract EAEA ${data.no} ${data.date} - ${data.contact_first_name} ${data.contact_last_name}.docx`
      .replace(/\//g, "-");

// Delete existing file if it exists
  const { data: existingFile, error: existingFileError } = await supabase
    .storage
    .from("contracts")
    .remove([formattedFileName]);

  if (existingFileError) {
    console.error("Error deleting existing file:", existingFileError);
    // Continue with upload even if delete fails
  }


  // Save the output DOCX file with the new name
  // Upload to Supabase Storage
  const { data: uploadData, error: uploadError } = await supabase
    .storage
    .from("contracts")
    .upload(formattedFileName, buffer, {
      contentType:
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
      upsert: true,
    });

  if (uploadError) {
    console.error("Error uploading document:", uploadError);
    throw uploadError;
  }

  console.log(`Document uploaded as ${formattedFileName}`);
  // Get the public URL for the uploaded file
  const { data: { signedUrl }, error: urlError } = await supabase
    .storage
    .from("contracts")
    .createSignedUrl(formattedFileName, 60 * 60 * 24); // 24 hours expiry

  if (urlError) {
    console.error("Error getting signed URL:", urlError);
    throw urlError;
  }

  // Save contract details to database
  const { data: contract, error: insertError } = await supabase
    .from("contracts")
    .insert({
      contact_id,
      student_id,
      date,
      number: data.no,
      emergency_contact_id: emergency_contact_id,
      first_timeslot_id: first_timeslot_id,
      second_timeslot_id: second_timeslot_id,
      subscription_type_id: subscription_type_id,
      file_url: signedUrl,
    })
    .select()
    .single();

  if (insertError) {
    console.error("Error inserting contract:", insertError);
    throw insertError;
  }

  console.log("Contract saved to database");

  return new Response(
    JSON.stringify(contract),
    { headers: { "Content-Type": "application/json" } },
  );
});

  // Function to convert date format from dd-mm-yyyy to yyyy-mm-dd
  function convertDateFormat(dateStr) {
    console.log("convertDateFormat called with dateStr:", dateStr);
    const [day, month, year] = dateStr.split('-');
    return `${year}-${month}-${day}`;
  }

  function reverseDateFormat(dateStr) {
    console.log("reverseDateFormat called with dateStr:", dateStr);
    const [year, month, day] = dateStr.split('-');
    return `${day}-${month}-${year}`;
  }

  
//validate data
function validateContractData(
  number: number,
  date: string,
  contact_id: string,
  student_id: string,
  emergency_contact_id: string,
) {
  if (!number || !date || !contact_id || !student_id || !emergency_contact_id) {
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

  // Validate UUIDs
  const uuidRegex =
    /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
  if (!uuidRegex.test(contact_id) || !uuidRegex.test(student_id)) {
    throw {
      error: "Invalid UUID format for contact_id or student_id",
      status: 400,
    };
  }

  // Validate date format
  const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
  if (!dateRegex.test(date)) {
    throw {
      error: "Date must be in YYYY-MM-DD format",
      status: 400,
    };
  }
}

/* To invoke locally:

  1. Run `supabase start` (see: https://supabase.com/docs/reference/cli/supabase-start)
  2. Make an HTTP request:

  curl -i --location --request POST 'http://127.0.0.1:54321/functions/v1/create_contract' \
    --header 'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZS1kZW1vIiwicm9sZSI6ImFub24iLCJleHAiOjE5ODM4MTI5OTZ9.CRXP1A7WOeoJeXxjNni43kdQwgnWNReilDMblYTn_I0' \
    --header 'Content-Type: application/json' \
    --data '{"name":"Functions"}'

*/
