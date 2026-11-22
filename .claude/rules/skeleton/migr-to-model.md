---
paths:
  - "app/Models/**"
  - "database/migrations/**"
---

# MIGR>MODEL: Migrations → models

From: MIGR (Migrations)  
To: MODEL (Models)

This card applies to every file at either end. Every rule here applies in full; the tag says who guarantees it.

## Carries

- **MIGR>MODEL-1** Tables: a new table with a model has a uuid primary key; a new foreign key has the type of the key it points to. `reviewer`
  - Check: Look at: new tables and foreign keys in new migrations. Break: a table with a model whose key is not uuid('id')->primary(); a foreign key of a different type than the key it points to. Not: tables without a model (pivot, jobs, cache). Cite: the line.
