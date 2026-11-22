---
paths:
  - "database/migrations/**"
---

# MIGR: Migrations

Location: `database/migrations/`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Must not

- **MIGR-1** Change or delete an existing migration; a database change is a new migration. `reviewer`
  - Check: Look at: the diff headers in database/migrations. Break: a migration that existed before the plan is changed, deleted or renamed. Not: new migration files, even when changed again in the same plan. Cite: the path from the header.

## Also applies here

- **ROUTE-4** (from ROUTE) New IDs in URLs and in page data are UUIDs; an integer ID already sent is existing debt. `reviewer`

## Arrows

- MIGR>MODEL: Migrations → models ([card](./migr-to-model.md))
