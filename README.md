# StringMailer PHP Script

A simple PHP script for sending emails based on URL parameters. The script uses PHP's built-in `mail()` function and requires no third-party libraries. It includes security features like input sanitization, a secret activation word, and logging. All settings are managed via a `config.ini` file.

## Features

- **Email Sending**: Sends emails based on URL parameters (`subject`, `mailbody`, `mailadresse`, `secret`).
- **Configuration**: Settings such as sender details, default values, and logging are defined in a `config.ini` file.
- **Security**:
    - Inputs are sanitized to prevent injection attacks.
    - A secret word (`SecretWord`) is required to send emails.
    - Option to disallow overriding the recipient email address via URL.
- **Logging**: Logs all actions (with optional debug mode) to a log file.
- **Output**: Supports plain text or JSON output, depending on configuration.
- **Error Handling**: Comprehensive error logging and notifications.

## Requirements

- PHP 7.4 or higher (tested with PHP 8.x).
- Write permissions for the directory where the log file is created.
- A working `mail()` configuration on the server (e.g., an SMTP server or Sendmail).
- A `config.ini` file in the same directory as the script.

## Installation

1. **Download the Script**:
   - Copy the `stringmailer.php` file to your web server directory (e.g., `/var/www/html`).

2. **Create Configuration File**:
   - Copy `config.example.ini` and rename it to `config.ini`.
   - Adjust the values in `config.ini` to match your requirements (see *Configuration* section).

3. **Set Write Permissions**:
   - Ensure the web server has write permissions for the log file (defined in `LogFilePath`):
     ```bash
     touch mail_log.txt
     chmod 664 mail_log.txt
     chown www-data:www-data mail_log.txt  # Adjust to your web server user
     ```

4. **Test the Script**:
   - Call the script via a URL, e.g.:
     ```
     http://yourdomain.com/stringmailer.php?subject=Test&mailbody=Hello&mailadresse=test@example.com&secret=yourSecretWord
     ```
   - Check the log file (`mail_log.txt`) for errors or debug information.

## Configuration

The `config.ini` file contains the following sections:

### `[MailSettings]`
- `FromEmail`: Sender email address (e.g., `sender@example.com`).
- `FromName`: Sender display name (e.g., `My Service`).
- `AllowToOverride`: `1` allows overriding the recipient email via URL, `0` always uses `DefaultMailTo`.

### `[DefaultValues]`
- `DefaultSubject`: Default subject if none is provided via URL.
- `DefaultMailBody`: Default email body if none is provided via URL.
- `DefaultMailTo`: Default recipient email address.

### `[Security]`
- `SecretWord`: Secret word required via the `secret` URL parameter.

### `[Logging]`
- `LogFilePath`: Path to the log file (e.g., `mail_log.txt`).
- `DebugMode`: `1` enables detailed debugging, `0` disables it.

### `[Output]`
- `UseJSON`: `1` outputs responses in JSON format, `0` outputs plain text.

An example configuration file is provided in `config.example.ini`.

## URL Parameters

The script accepts the following GET parameters:

| Parameter      | Description                                      | Default Value (if not provided)             |
|----------------|--------------------------------------------------|---------------------------------------------|
| `subject`      | Email subject                                   | `DefaultSubject` from `config.ini`          |
| `mailbody`     | Email body text                                 | `DefaultMailBody` from `config.ini`         |
| `mailadresse`  | Recipient email address (if `AllowToOverride=1`) | `DefaultMailTo` from `config.ini`       |
| `secret`       | Secret word for activation                      | Must match `SecretWord` in `config.ini`     |

**Example URL**:
[http://yourdomain.com/stringmailer.php?subject=Test+Subject&mailbody=Hello+World&mailadresse=test@example.com&secret=mySecret123](http://yourdomain.com/stringmailer.php?subject=Test+Subject&mailbody=Hello+World&mailadresse=test@example.com&secret=mySecret123)

## Output

- **Plain Text Output** (`UseJSON=0`):
  ```
  Email sent successfully!
  ```
  or
  ```
  Failed to send email.
  ```

- **JSON Output** (`UseJSON=1`):
  ```json
  {
      "success": true,
      "message": "Email sent successfully!"
  }
  ```
  or
  ```json
  {
      "success": false,
      "message": "Failed to send email."
  }
  ```

## Logging

All actions are logged to the file specified in `LogFilePath`.

If `DebugMode=1`, additional debug information (e.g., received parameters) is logged.

Example log entry:
```
[2025-05-15 12:20:00] [INFO] Script execution started.
[2025-05-15 12:20:00] [DEBUG] Received parameters - Subject: Test, MailTo: test@example.com, Body: Hello, Secret: mySecret123
[2025-05-15 12:20:00] [INFO] Email sent successfully to test@example.com with subject 'Test'.
```

## Security Notes

- **Secret Word**: Use a strong `SecretWord` and do not expose it publicly.
- **Input Validation**: The script sanitizes all inputs, but you should restrict access to the script (e.g., via IP whitelisting).
- **Recipient Override**: Set `AllowToOverride=0` to enforce the default recipient email and prevent misuse.
- **Debug Mode**: Disable `DebugMode` in production (`DebugMode=0`) to avoid exposing sensitive information.
- **Error Display**: Disable `display_errors` in production:
  ```php
  ini_set('display_errors', 0);
  ```

## Troubleshooting

- **Email Not Sent**:
  - Verify the server's mail configuration (e.g., `php.ini` or SMTP setup).
  - Check the log file for error messages.

- **"Invalid or missing secret word"**:
  - Ensure the `secret` parameter matches `SecretWord` in `config.ini`.

- **"Unable to load config.ini"**:
  - Verify that `config.ini` exists and is readable.

- **No Log File**:
  - Ensure the web server has write permissions for `LogFilePath`.

## License

This script is provided under the MIT License. See LICENSE for details (if available).

## Contact

For questions or support, please contact the developer.
