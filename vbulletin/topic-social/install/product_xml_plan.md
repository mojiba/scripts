# Product XML Plan

## Purpose

This document maps the PHP scaffold to the vBulletin 4 product system.

## Product identity

- Product ID: `topicsocial`
- Title: `Topic Social`
- Version: `0.1.0`

## Product components

### Files referenced by plugin code
The product XML should load code from the following project files:

- `vbulletin/topic-social/src/plugin_core.php`
- `vbulletin/topic-social/src/template_engine.php`
- `vbulletin/topic-social/src/extractors.php`
- `vbulletin/topic-social/src/publishers.php`
- `vbulletin/topic-social/src/service.php`
- `vbulletin/topic-social/src/vbulletin_integration.php`
- `vbulletin/topic-social/src/hooks.php`

### Option group
Suggested option group:
- `Topic Social`

### Options to register
- `topicsocial_enabled`
- `topicsocial_mode`
- `topicsocial_allow_resend`
- `topicsocial_forumids`
- `topicsocial_message_template`
- `topicsocial_cuttly_enabled`
- `topicsocial_cuttly_api_key`
- `topicsocial_telegram_enabled`
- `topicsocial_telegram_bot_token`
- `topicsocial_telegram_chat_id`
- `topicsocial_x_enabled`
- `topicsocial_x_api_key`
- `topicsocial_x_api_secret`
- `topicsocial_x_access_token`
- `topicsocial_x_access_token_secret`
- `topicsocial_log_enabled`

## Hook targets to register

### Automatic send
Register a hook after new thread creation that can call:
- `topicsocial_hook_newthread_complete($threadid)`

### Manual route
Register a handler path for:
- `misc.php?do=topicsocial_send&threadid={threadid}`

This should call:
- `topicsocial_handle_manual_send_request()`

### Topic page status box
Register a hook in thread page rendering so the product can inject:
- `topicsocial_render_admin_box_html($threadid)`

## Notes

The exact hook names should be confirmed against the target vBulletin 4.2.5 product XML conventions in your installation.
