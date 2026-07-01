# Topic Social vBulletin Integration Notes

## Planned integration points

### Automatic send on thread creation
A plugin hook should call:

- `topicsocial_hook_newthread_complete($threadid)`

This should be wired to the vBulletin lifecycle after the thread and first post are fully created.

### Manual send action
A custom action can route through:

- `topicsocial_handle_manual_send_request()`

Suggested endpoint shape:

- `misc.php?do=topicsocial_send&threadid={threadid}`

Only admins should be allowed to trigger this action.

### Topic page admin box
When rendering the topic page, prepend or inject:

- `topicsocial_render_admin_box_html($threadid)`

This should only display for admins and should show:
- current status
- last attempt date
- last error if any
- send/resend button

## Notes

The exact hook names may vary depending on the target vBulletin 4 installation and product XML implementation. The PHP scaffolding here is intended to isolate the business logic from hook registration details.
