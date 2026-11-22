---
paths:
  - "database/factories/**"
---

# FACT: Factories

Location: `database/factories/`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Does

- **FACT-1** Knows what a valid record looks like. `note`

## Must not

- **FACT-2** Be used outside database/ (tests excepted). `machine` (PHPStan: disallowedNamespaces Database\Factories and disallowedStaticCalls HasFactory::factory() (allowIn database/))

## Also applies here

- **MODEL-3** (from MODEL) Every model has a factory. `reviewer`

## Arrows

- FACT>MODEL: Factories → tests and seeders ([card](./fact-to-model.md))
