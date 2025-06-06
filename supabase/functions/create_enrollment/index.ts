import { createClient } from "https://esm.sh/@supabase/supabase-js@2.46.1";
import sgMail from "https://esm.sh/@sendgrid/mail";
import { EnrollmentMessage } from "../messages/EnrollmentMessage.ts";

Deno.serve(async (req) => {
  const { email } = await req.json();

  const supabase = createClient(
    Deno.env.get("SUPABASE_URL") ?? "",
    Deno.env.get("SUPABASE_ANON_KEY") ?? "",
    {
      global: { headers: { Authorization: req.headers.get("Authorization")! } },
    },
  );

  let status = "pending";

  // Configure SendGrid API
  sgMail.setApiKey(Deno.env.get("SENDGRID_API_KEY") ?? "");

  // Generate the message
  const enrollmentMessage = new EnrollmentMessage(email);
  const msg = enrollmentMessage.getMessage();

  try {
    await sgMail.send(msg);
    console.log("Email sent successfully");
    status = "sent";
  } catch (error) {
    console.error("Error sending email:", error);
    status = "error";
  }

  // Save enrollment details to the database
  const { data: enrollment, error: insertError } = await supabase
    .from("enrollments")
    .insert({
      email: email,
      status: status,
    })
    .select()
    .single();

  if (insertError) {
    console.error("Error inserting enrollment:", insertError);
    return new Response("Database error", { status: 500 });
  }

  return new Response(JSON.stringify(enrollment), {
    headers: { "Content-Type": "application/json" },
  });
});
