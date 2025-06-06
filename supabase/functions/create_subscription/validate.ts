export function validateContractData(
  contract_id: string,
  starting_date: string,
) {
  console.log("validateContractData called with:", {
    contract_id,
    starting_date,
  });

  // Check for required fields
  if (!contract_id || !starting_date) {
    console.log("Missing required fields detected");
    throw new Error("Missing required fields");
  }

  // Validate UUID format using regex
  console.log("Validating UUID format");
  const uuidRegex =
    /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
  if (!uuidRegex.test(contract_id) || !uuidRegex.test(contract_id)) {
    console.log("Invalid UUID format detected");
    throw new Error("Invalid UUID format for contract_id");
  }
  console.log("Contract data validation successful");
}