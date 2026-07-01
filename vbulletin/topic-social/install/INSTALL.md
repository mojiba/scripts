# Installation Notes

## Current status

The project currently includes:
- PHP scaffold files
- SQL schema draft
- product XML draft
- option planning documents
- multi-channel publishing flow
- Telegram and WhatsApp channel scaffolding

## Manual installation outline

1. Place the project files where vBulletin can load them.
2. Create the database tables from `install/schema.sql`.
3. Review and adapt `install/product-topicsocial.xml` against a real exported product from your vBulletin 4.2.5 installation.
4. Register product hooks from the validated XML.
5. Create the AdminCP options listed in `install/options.md`.
6. Configure Telegram, WhatsApp, X, Cutt.ly, monitored forum IDs, and message template.

## Validation guidance for real import

Before attempting a real import:
- export any small working product from the target vBulletin 4.2.5 instance;
- compare its XML structure with `product-topicsocial.xml`;
- align node names, attributes, plugin structures, and option structures;
- verify the actual hook names and available variables for `newthread_complete`, `showthread_start`, and `showthread_complete`.

## Important

The product XML in this repository is a stronger draft, but still should be treated as a planning file until validated against the exact product XML schema and hook behavior of your target vBulletin 4.2.5 installation.
