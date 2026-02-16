# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Plugin Overview

`tool_bulkmessaging` is a Moodle admin tool plugin that allows site administrators to send bulk notifications to filtered or all users. It lives at `admin/tool/bulkmessaging/` within a Moodle 5.0 installation. The parent Moodle instance has its own CLAUDE.md at the repository root with broader build/test/architecture guidance.

## Architecture

### Request Flow

1. **Compose** (`index.php`): Admin filters users via Moodle's `user_filtering` API, composes a message using `message_form`, then confirms
2. **Queue** (`index.php` on confirm): Creates a `tool_bulkmessaging_log` record (status 0=queued), chunks user IDs into batches, queues one `send_bulk_message` adhoc task per batch
3. **Process** (cron): Each adhoc task sends notifications via `message_send()` using the `bulknotification` message provider, atomically updates sent/failed counts, and the last task marks status 2=completed
4. **History** (`history.php`): Displays log table with actions — cancel (queued), stop (processing), start/retry (failed/stopped), delete (finished), view detail

### Status Lifecycle

`tool_bulkmessaging_log.status`: 0=queued → 1=processing → 2=completed | 3=failed | 4=cancelled | 5=stopped

Cancel/stop works by setting status and deleting remaining adhoc tasks from `task_adhoc` matching the log ID in `customdata`.

### Key Classes

- `classes/form/message_form.php` — Moodle form with subject, HTML editor body, send-to-all checkbox
- `classes/task/send_bulk_message.php` — Adhoc task that sends one batch; uses noreply user as sender; checks for cancellation before processing
- `classes/event/bulk_message_sent.php` — Event fired when message is queued (not when delivered)
- `classes/privacy/provider.php` — Null provider (no personal data stored)

### Database

Single table `tool_bulkmessaging_log` (defined in `db/install.xml`). Tracks subject, body, sender, recipient/sent/failed counts, status, filter data (JSON), and timestamps. Indexed on `status` and `timecreated`.

### Configuration

Two admin settings (Site admin > Plugins > Admin tools > Bulk messaging settings):
- `batchsize` — Users per adhoc task batch (default 50)
- `maxrecipients` — Cap on total recipients per message (0 = unlimited)

### Capability

`tool/bulkmessaging:sendmessage` — Required at system context. Granted to manager archetype by default. Flagged as RISK_SPAM.

### Message Provider

`bulknotification` in `db/messages.php` — Defaults to popup + email + airnotifier enabled.

### Navigation

Plugin registers two admin external pages under Site admin > Users > Accounts: the compose page (`toolbulkmessaging`) and history page (`toolbulkmessaginghistory`). Tab navigation between them is rendered by `tool_bulkmessaging_render_tabs()` in `locallib.php`.

## Development Commands

This plugin has no standalone build steps. Use the parent Moodle commands:

```bash
# After code changes
php admin/cli/purge_caches.php

# After version.php bump
php admin/cli/upgrade.php

# Process queued bulk messages
php admin/cli/cron.php

# Run PHPUnit tests (if tests exist)
vendor/bin/phpunit admin/tool/bulkmessaging/tests/

# Lint
npx grunt eslint --files=admin/tool/bulkmessaging/amd/src/
npx grunt stylelint --files=admin/tool/bulkmessaging/scss/
```

## Conventions

- Component name is `tool_bulkmessaging` — use this for all `get_string()`, `get_config()`, and `@package` annotations
- All strings go in `lang/en/tool_bulkmessaging.php`
- Use `$DB` methods exclusively (no raw SQL except via `$DB->execute()` or `$DB->get_records_sql()`)
- Version format is `YYYYMMDDXX` in `version.php`; bump it for any DB schema or capability change
- Messages are sent as notifications (`$message->notification = 1`) from the noreply user, not as personal messages
