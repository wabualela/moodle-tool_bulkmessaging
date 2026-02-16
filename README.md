# Bulk Messaging (`tool_bulkmessaging`)

A Moodle admin tool plugin that allows site administrators to send bulk notifications to filtered or all users.

## Requirements

- Moodle 5.0+ (requires version 2025040800)

## Installation

1. Copy the `bulkmessaging` folder into `admin/tool/` in your Moodle installation.
2. Visit **Site administration > Notifications** to complete the installation.
3. Run `php admin/cli/purge_caches.php` if needed.

## Features

- **User filtering** — Use Moodle's built-in user filters (name, email, role, cohort, etc.) to target specific recipients.
- **Send to all** — Option to send to all active users on the site, bypassing filters.
- **CSV upload** — Upload a CSV file with an "email" column to target specific users by email address.
- **Personalization placeholders** — Use `{fullname}`, `{firstname}`, `{lastname}`, `{email}`, and `{username}` in the subject and body.
- **Rich text editor** — Compose HTML messages using Moodle's standard editor.
- **Background processing** — Messages are queued and sent in batches via Moodle's adhoc task system, keeping the UI responsive.
- **Real-time progress tracking** — Monitor delivery progress on the history page.
- **Message history** — View, cancel, stop, restart, or delete past messages.
- **Configurable batch size and recipient limits** — Control processing load via admin settings.

## Usage

Navigate to **Site administration > Users > Accounts > Bulk messaging**.

1. **Compose** — Filter users or upload a CSV, compose your message, and confirm.
2. **Queue** — The message is queued and split into batches for background processing.
3. **Process** — Run Moodle cron (`php admin/cli/cron.php`) to deliver queued messages.
4. **History** — View delivery status, cancel queued messages, stop in-progress messages, or retry failed ones.

## Message Lifecycle

| Status | Description |
|---|---|
| Queued | Message created, waiting for cron to process |
| Processing | Cron is actively sending batches |
| Completed | All batches sent successfully |
| Failed | One or more batches encountered errors |
| Cancelled | Cancelled by admin before processing began |
| Stopped | Stopped by admin during processing (already-sent messages remain delivered) |

## Configuration

**Site administration > Plugins > Admin tools > Bulk messaging settings**

| Setting | Default | Description |
|---|---|---|
| Batch size | 50 | Number of messages sent per background task batch |
| Maximum recipients | 0 (unlimited) | Cap on total recipients per message. Set to 0 for no limit. |

## Capability

| Capability | Context | Default role | Risk |
|---|---|---|---|
| `tool/bulkmessaging:sendmessage` | System | Manager | RISK_SPAM |

## License

This plugin is licensed under the [GNU GPL v3 or later](http://www.gnu.org/copyleft/gpl.html).

Copyright 2026 Moddaker
