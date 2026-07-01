# Product XML Plan

## Purpose

This document maps the PHP scaffold to the vBulletin 4 product system.

## Product identity

- Product ID: `topicsocial`
- Title: `Topic Social`
- Version: `0.3.0`

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
- `topicsocial_whatsapp_enabled`
- `topicsocial_whatsapp_api_url`
- `topicsocial_whatsapp_token`
- `topicsocial_whatsapp_to`
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
Register a showthread lifecycle hook that can call:
- `topicsocial_hook_showthread_start()`

### Topic page status box
Register a hook in thread page rendering so the product can inject:
- `topicsocial_render_admin_box_html($threadid)`

## Validation result

### What is better now
- The PHP side is separated enough to be referenced from product hooks.
- The hook intent is clearly documented.
- Options are enumerated in one place.
- Channel support now reflects Telegram, WhatsApp, and X.

### What is still not validated as import-ready
- The exact XML schema expected by the vBulletin 4.2.5 product importer has not been confirmed in this repository.
- The hook identifiers must be verified against the target installation.
- The `showthread_complete` output injection approach may need adjustment depending on the actual variable exposed by the hook.
- AdminCP option definitions are not yet encoded as real importable XML nodes.
- Phrase definitions and templates are not yet included.

## Recommended path to real import

1. Export a minimal working product from the target vBulletin 4.2.5 installation.
2. Use that exported file as the canonical XML skeleton.
3. Rebuild `product-topicsocial.xml` to match the exact schema from that export.
4. Add optiongroup, options, phrases, and plugin records in the same structure.
5. Validate hook variable names in the target installation before first import.
