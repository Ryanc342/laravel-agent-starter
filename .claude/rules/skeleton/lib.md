---
paths:
  - "resources/js/lib/**"
  - "resources/js/hooks/**"
---

# LIB: Frontend helpers

Location: `resources/js/lib/, resources/js/hooks/`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Does

- **LIB-1** Small functions shared across the frontend. `note`

## Also applies here

- **PAGE-10** (from PAGE) Work out a count, total, yes/no decision, list of accepted values or today's date that a screen shows or uses: the frontend does not count, sum, filter, compare dates or read the clock; the backend sends these. Allowed: checking whether a received list is empty, UI state, and formatting a received value with a shared helper. `reviewer`
- **COMP-10** (from COMP) A component or hook that calls useState, useReducer or useEffect, sends a request, or writes storage or cookies has a Vitest file at the same path under tests/js/; a change to its behaviour changes that test. `reviewer`

## Arrows

- WEB>HTTP: Frontend → backend (requests) ([card](./web-to-http.md))
