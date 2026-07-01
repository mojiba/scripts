# Topic Social Plugin Specification

## Objective

Create a vBulletin 4.2.5 plugin that publishes selected topics to multiple social channels, starting with Telegram, Discord, and X.

## Environment

- vBulletin 4.2.5
- PHP 7.0.33
- MariaDB 10.5.x

## Main capabilities

1. Automatic mode: publish when a topic is created in a monitored forum.
2. Manual mode: allow admin-triggered publishing from the topic page.
3. Manual resend: allow republishing after edits.
4. Template-based outgoing message.
5. Optional URL shortening through Cutt.ly.
6. Logging of send attempts.
7. Channel-oriented architecture for future expansion.

## Admin-facing options

### General
- `topicsocial_enabled`
- `topicsocial_mode` (`auto` or `manual`)
- `topicsocial_allow_resend`

### Scope
- `topicsocial_forumids` (comma-separated list)

### Message formatting
- `topicsocial_message_template`
  - supported placeholders:
    - `{title}`
    - `{price}`
    - `{url}`
    - `{hashtags}`

### Cutt.ly
- `topicsocial_cuttly_enabled`
- `topicsocial_cuttly_api_key`

### Telegram
- `topicsocial_telegram_enabled`
- `topicsocial_telegram_bot_token`
- `topicsocial_telegram_chat_id`

### Discord
- `topicsocial_discord_enabled`
- `topicsocial_discord_webhook_url`

### X
- `topicsocial_x_enabled`
- `topicsocial_x_api_key`
- `topicsocial_x_api_secret`
- `topicsocial_x_access_token`
- `topicsocial_x_access_token_secret`

### Logs
- `topicsocial_log_enabled`

## Internal rules

These behaviors are internal and not exposed as panel options:

- Extract price from title first.
- If not found, extract price from the first post.
- Publish even when no price is found.
- Use the first image from the first post when present.
- Remove detected price from the title used in the outgoing message.
- Use `{hashtags}` internally as `#ad #anúncio`.
- Show admin status/actions only to admins.
- Use long URL if Cutt.ly fails.
- Do not let external publishing failure break topic creation.
- Treat channels generically so future networks can be added without rewriting the full service flow.

## Proposed runtime flow

### Automatic mode
1. New thread is created.
2. Check if plugin is enabled.
3. Check if forum ID is monitored.
4. Build topic payload.
5. Extract title, price, URL, and first image.
6. Render template.
7. Publish to enabled channels.
8. Save status and log.

### Manual mode
1. Admin opens topic page.
2. Admin sees status box.
3. Admin clicks send or resend.
4. Plugin rebuilds payload from current topic state.
5. Plugin publishes to enabled channels.
6. Save status and log.

## Data to persist

A dedicated table should track publishing state per thread:

- threadid
- last_attempt_at
- last_success_at
- status
- last_channels
- last_message_text
- last_url
- last_image_url
- last_error

A separate log table should track attempts and responses per channel.
