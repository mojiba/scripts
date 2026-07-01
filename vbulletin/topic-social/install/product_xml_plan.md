# Product XML Plan

## Status

The product XML has been rebuilt to follow the structural pattern exported by vBulletin 4.2.5 products in this installation.

## Product identity

- Product ID: `topicsocial`
- Title: `Topic Social`
- Version: `0.4.0`

## Included sections

The rebuilt XML now contains real vBulletin-style sections:

- `dependencies`
- `codes`
- `templates`
- `stylevardfns`
- `stylevars`
- `plugins`
- `phrases`
- `options`
- `helptopics`
- `cronentries`
- `faqentries`
- `navigation`

## Install / uninstall behavior

### Install
The install code now:
- validates `curl_init`
- validates `json_decode`
- creates `plugin_topicsocial_status`
- creates `plugin_topicsocial_log`

### Uninstall
The uninstall code currently drops:
- `plugin_topicsocial_log`
- `plugin_topicsocial_status`

## Hook mapping

### Automatic publish
- hook: `threadfpdata_postsave`
- action: call `topicsocial_hook_newthread_complete()`

This choice was based on the exported `dbtech_tweetposter` structure from the target environment.

### Manual action route
- hook: `showthread_start`
- action: call `topicsocial_hook_showthread_start()`

### Admin box injection
- hook: `showthread_complete`
- action: prepend admin box into `template_hook['postbit_start']`

## Remaining validation risks

Even after reconstruction, the following should still be verified in the real installation:

1. whether `threadfpdata_postsave` provides the expected thread identifiers in every posting path;
2. whether `template_hook['postbit_start']` is the best insertion point for the admin box on your theme stack;
3. whether additional phrases or templates become desirable after first import;
4. whether uninstall should preserve data instead of dropping tables.

## Why this is now closer to real import

The rebuilt XML now matches the exported product pattern you provided, instead of using a custom draft-only structure.
