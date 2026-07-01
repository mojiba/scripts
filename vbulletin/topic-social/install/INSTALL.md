# Installation Notes

## Current status

The project now includes a rebuilt `product-topicsocial.xml` in the structural style exported by your vBulletin 4.2.5 installation.

## Import path

1. Place the PHP source files where vBulletin can load them.
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
