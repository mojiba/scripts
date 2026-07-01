# Installation Notes

## Current status

The project currently includes:
- PHP scaffold files
- SQL schema draft
- product XML draft
- option planning documents
- multi-channel publishing flow

## Manual installation outline

1. Place the project files where vBulletin can load them.
2. Create the database tables from `install/schema.sql`.
3. Register product hooks from `install/product-topicsocial.xml`.
4. Create the AdminCP options listed in `install/options.md`.
5. Configure Telegram, Discord, X, Cutt.ly, monitored forum IDs, and message template.

## Important

The product XML in this repository is a hardened draft, but still should be validated against the exact hook names and product XML schema expected by your vBulletin 4.2.5 installation before import.
