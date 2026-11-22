---
paths:
  - "app/Models/**"
  - "database/factories/**"
  - "database/seeders/**"
  - "tests/**"
---

# FACT>MODEL: Factories → tests and seeders

From: FACT (Factories)  
To: MODEL (Models), TEST (Tests), SEED (Seeders)

This card applies to every file at either end. Every rule here applies in full; the tag says who guarantees it.

## Carries

- **FACT>MODEL-1** Tests and seeders create records with factories. `reviewer`
  - Check: Look at: how tests and seeders make records. Break: new Model, create(), forceCreate() or DB inserts instead of the factory. Not: the factory with states and attributes. Cite: the line.
