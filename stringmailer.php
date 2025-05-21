<?php
/**
 * stringmailer.php
 * A PHP script to send emails using parameters passed via URL.
 * Uses PHP's built-in mail() function, no third-party libraries.
 * Requires a secret word for activation, logs the process, and supports debug mode.
 * All settings are loaded from a config.ini file.
 * Inputs are sanitized and securely checked.
 * Output can be in JSON format based on configuration.
 * Includes an option to disallow overriding the "To" email address via URL.
 * Handles special characters like ! in secret word via URL decoding.
 */

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Logs a message to the specified log file.
 * @param string $message The message to log.
 * @param string $logFilePath Path to the log file.
 * @param bool $isDebug Whether this is a debug message.
 * @param bool $debugMode Whether debug mode is enabled.
 */
function logMessage($message, $logFilePath, $isDebug = false, $debugMode = false) {
    if ($isDebug && !$debugMode) {
        return; // Skip debug messages if debug mode is off
    }
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] " . ($isDebug ? "[DEBUG] " : "[INFO] ") . $message . "\n";
    file_put_contents($logFilePath, $logEntry, FILE_APPEND);
}

/**
 * Sanitizes input to prevent injection attacks and ensure safety.
 * Replaces deprecated FILTER_SANITIZE_STRING with custom sanitization.
 * @param string|null $input The input to sanitize.
 * @param bool $isEmail Whether the input is an email address.
 * @return string|null The sanitized input, or null if invalid.
 */
function sanitizeInput($input, $isEmail = false) {
    if ($input === null || trim($input) === '') {
        return null;
    }
    // Basic sanitization: remove HTML tags, preserve special characters for secret word
    $input = trim($input);
    $input = strip_tags($input); // Remove HTML tags

    // For non-email inputs (e.g., secret word), skip htmlspecialchars to preserve special characters like !
    if (!$isEmail) {
        // Only remove control characters, preserve printable characters including !
        $input = preg_replace('/[\x00-\x1F\x7F]/u', '', $input);
    } else {
        // For email inputs, apply stricter sanitization
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8'); // Encode special characters
        $input = filter_var($input, FILTER_SANITIZE_EMAIL);
        if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
            return null; // Invalid email format
        }
    }

    return $input;
}

/**
 * Sends an email using PHP's built-in mail() function.
 * @param array $mailSettings Email settings from config (FromEmail, FromName).
 * @param string $to Recipient email address.
 * @param string $subject Email subject.
 * @param string $body Email body.
 * @return bool Returns true if email was sent successfully, false otherwise.
 */
function sendEmail($mailSettings, $to, $subject, $body) {
    // Prepare headers
    $headers = "From: {$mailSettings['FromName']} <{$mailSettings['FromEmail']}>\r\n";
    $headers .= "Reply-To: {$mailSettings['FromEmail']}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Ensure subject is safe for email headers (remove newlines)
    $subject = str_replace(["\r", "\n"], '', $subject);

    // Send the email using mail()
    return mail($to, $subject, $body, $headers);
}

/**
 * Outputs the response in the specified format (plain text or JSON).
 * @param string $message The message to output.
 * @param bool $success Whether the operation was successful.
 * @param bool $useJSON Whether to output in JSON format.
 */
function outputResponse($message, $success, $useJSON) {
    if ($useJSON) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message
        ]);
    } else {
        echo $message;
    }
}

// Main script execution starts here

// Load configuration from config.ini
$config = parse_ini_file('config.ini', true);
if ($config === false) {
    die('Error: Unable to load config.ini');
}

// Extract configuration sections
$mailSettings = $config['MailSettings'];
$defaultValues = $config['DefaultValues'];
$security = $config['Security'];
$logging = $config['Logging'];
$output = $config['Output'];

// Extract logging and output settings
$logFilePath = $logging['LogFilePath'];
$debugMode = (bool)$logging['DebugMode'];
$useJSON = (bool)$output['UseJSON'];
$allowToOverride = (bool)$mailSettings['AllowToOverride'];

// Log script start
logMessage("Script execution started.", $logFilePath);

// Get and sanitize URL parameters (using GET method)
$subject = sanitizeInput($_GET['subject'] ?? null);
$mailBody = sanitizeInput($_GET['mailbody'] ?? null);
$mailToInput = sanitizeInput($_GET['mailadresse'] ?? null, true);
// Decode secret parameter only if it exists and is a string to avoid deprecated warning
$secretWord = isset($_GET['secret']) && is_string($_GET['secret']) ? sanitizeInput(urldecode($_GET['secret'])) : null;

// Use default values if sanitized inputs are null
$subject = $subject ?: $defaultValues['DefaultSubject'];
$mailBody = $mailBody ?: $defaultValues['DefaultMailBody'];

// Determine the "To" email address based on AllowToOverride setting
$mailTo = $defaultValues['DefaultMailTo']; // Default value
if ($allowToOverride && $mailToInput !== null) {
    $mailTo = $mailToInput;
    logMessage("To email overridden via URL: $mailTo", $logFilePath, true, $debugMode);
} else {
    logMessage("To email override not allowed, using default: $mailTo", $logFilePath, true, $debugMode);
}

// Log received parameters (debug mode)
logMessage(
    "Received parameters - Subject: $subject, MailTo: $mailTo, Body: $mailBody, Secret: " . ($secretWord ?? 'not provided'),
    $logFilePath,
    true,
    $debugMode
);

// Validate secret word
if ($secretWord === null || $secretWord !== $security['SecretWord']) {
    $errorMessage = "Error: Invalid or missing secret word.";
    logMessage($errorMessage, $logFilePath);
    outputResponse($errorMessage, false, $useJSON);
    exit;
}

// Log successful secret word validation
logMessage("Secret word validated successfully.", $logFilePath);

// Attempt to send the email
$emailSent = sendEmail($mailSettings, $mailTo, $subject, $mailBody);

// Log the result of the email sending and output response
if ($emailSent) {
    $message = "Email sent successfully to $mailTo with subject '$subject'.";
    logMessage($message, $logFilePath);
    outputResponse("Email sent successfully!", true, $useJSON);
} else {
    $message = "Failed to send email to $mailTo.";
    logMessage($message, $logFilePath);
    outputResponse("Failed to send email.", false, $useJSON);
}

// Log script end
logMessage("Script execution completed.", $logFilePath);

?>