# Topic Social for vBulletin 4

Plugin project for vBulletin 4.2.5 that publishes selected topics to X and Telegram.

## Goals

- Support automatic and manual publishing modes
- Allow manual resend of previously published topics
- Restrict operation to configured forum IDs
- Use an editable message template with placeholders
- Support optional URL shortening via Cutt.ly
- Keep compatibility with PHP 7.0.33 and MariaDB 10.5

## Project layout

- `docs/specification.md` - functional specification
- `src/plugin_core.php` - core helper functions and option access
- `src/template_engine.php` - message template rendering helpers
- `src/extractors.php` - price, image, and URL extraction helpers
- `src/publishers.php` - Telegram, X, and Cutt.ly publishing helpers
- `install/schema.sql` - database tables used by the plugin
- `install/options.md` - option varnames and intended meanings

## Current status

Scaffold and technical design in progress on branch `feature/vbulletin-topic-social`.
