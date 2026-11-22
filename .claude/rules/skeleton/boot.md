---
paths:
  - "app/Providers/**"
  - "config/**"
  - "bootstrap/**"
---

# BOOT: Bootstrapping and config

Location: `app/Providers/, config/, bootstrap/`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Does

- **BOOT-1** Settings at startup, and what an error shows: 403, 404, 500 and 503 show the error page. `note`

## Must

- **BOOT-2** A 403, 404, 500 or 503 response shows only the fixed text of its status; it never includes an exception message, stack trace, SQL, file path or setting. Validation errors (422) show their field messages. `reviewer`
  - Check: Look at: withExceptions() in bootstrap/app.php, render and respond callbacks, error page props, debug in config/app.php. Break: a response including $e->getMessage(), a trace, getFile(), SQL or a setting; 'debug' => env('APP_DEBUG', true); abort(500, $e->getMessage()). Not: report() and Log; the error page receiving only the status. Cite: the line.

## Also applies here

- **ACT-14** (from ACT) Dispatch events or register listeners, observers or model event callbacks in app code (app/, bootstrap/, config/); events Laravel or a package fires on its own are not covered, and tests may listen to model events. `reviewer`
