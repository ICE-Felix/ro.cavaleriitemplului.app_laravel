import { createClient } from "https://esm.sh/@supabase/supabase-js@2.46.1";
import sgMail from "https://esm.sh/@sendgrid/mail";
import { EnrollmentMessage } from "../messages/EnrollmentMessage.ts";
import { RenewSubscription } from "../messages/RenewSubscription.ts";

Deno.serve(async (req: Request) => {
  try {
    const { to, template, data } = await req.json();

    if (!to || typeof to !== "string") {
      return new Response("Invalid 'to' email address.", { status: 400 });
    }

    const supabase = createClient(
      Deno.env.get("SUPABASE_URL") ?? "",
      Deno.env.get("SUPABASE_ANON_KEY") ?? "",
      {
        global: { headers: { Authorization: req.headers.get("Authorization")! } },
      },
    );

    // Configure SendGrid API
    sgMail.setApiKey(Deno.env.get("SENDGRID_API_KEY") ?? "");

    // Generate the message based on template
    let msg;
    switch (template) {
      case "renewSubscription":
        const renewMessage = new RenewSubscription(
          to,
          data?.name ?? "",
          data?.invoiceUrl ?? "",
          data?.subscription ?? [],
        );
        msg = renewMessage.getMessage();
        break;
      default:
        const enrollmentMessage = new EnrollmentMessage(to);
        msg = enrollmentMessage.getMessage();
        break;
    }

    // Create the final message object with CC and BCC
    const msgWithAttachment = {
      ...msg,
      cc: ["stefan.bordei@eaea.ro", "contact@eaea.ro"],
      bcc: ["alex.bordei@whiz.ro"],
    };

    // Add attachment only if pdfFile exists in data
    if (data?.pdfFile?.blob) {
      const attachmentContent = data.pdfFile.blob instanceof Uint8Array
        ? blobToBase64(data.pdfFile.blob)
        : blobToBase64(new Uint8Array(data.pdfFile.blob));

      msgWithAttachment.attachments = [{
        content: data.pdfFile.blob,
        filename: data.pdfFile.fileName,
        type: "application/pdf",
        disposition: "attachment",
      }];
    }

    // Send the email
    await sgMail.send(msgWithAttachment);
    console.log("Email sent successfully");

    return new Response("Email sent successfully", {
      headers: { "Content-Type": "application/json" },
    });
  } catch (error) {
    console.error("Error:", error);
    return new Response(
      JSON.stringify({ error: "There is a problem sending the email" }),
      { status: 500, headers: { "Content-Type": "application/json" } }
    );
  }
});

// Helper function to convert Uint8Array to base64
function blobToBase64(data: Uint8Array): string {
  return btoa(String.fromCharCode(...data));
}