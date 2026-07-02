# Installation Notes

## Current status

The project now uses `includes/topicsocial/` as the runtime path inside the vBulletin installation.

## Filesystem layout expected by the product hooks

These runtime files must exist inside the target vBulletin install:

- `includes/topicsocial/plugin_core.php`
- `includes/topicsocial/template_engine.php`
- `includes/topicsocial/extractors.php`
- `includes/topicsocial/publishers.php`
- `includes/topicsocial/service.php`
- `includes/topicsocial/vbulletin_integration.php`
- `includes/topicsocial/hooks.php`

## Import path

1. Place the PHP source files under `includes/topicsocial/`.
2. In AdminCP, import `vbulletin/topic-social/install/product-topicsocial.xml`.
3. Confirm the install code creates the Topic Social tables.
4. Review the new `Topic Social` option group in AdminCP.
5. Configure monitored forums, template, Telegram, WhatsApp, X, and logging.
6. Test manual sending on a thread.
7. Test automatic sending with a new thread in a monitored forum.

## Important validation checklist

After import, verify:
- the product imports without XML schema errors;
- the option group and settings appear correctly;
- the auto hook fires on new thread creation;
- the manual route returns correctly to `showthread.php`;
- the admin box appears in the expected location;
- no theme/plugin conflict occurs on `showthread_complete`.

## Fallback plan

If the admin box does not appear in the desired position, the first thing to adjust should be the insertion hook and target template hook variable, not the whole product structure.
