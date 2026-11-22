---
paths:
  - "database/seeders/**"
---

# SEED: Seeders

Location: `database/seeders/`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Does

- **SEED-1** Test accounts for development, made with factories. `note`

## Also applies here

- **FACT-2** (from FACT) Be used outside database/ (tests excepted). `machine` (PHPStan: disallowedNamespaces Database\Factories and disallowedStaticCalls HasFactory::factory() (allowIn database/))

## Arrows

- FACT>MODEL: Factories → tests and seeders ([card](./fact-to-model.md))
