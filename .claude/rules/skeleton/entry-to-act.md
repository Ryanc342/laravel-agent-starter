---
paths:
  - "app/Actions/**"
  - "app/Console/**"
  - "app/Http/Controllers/**"
  - "app/Http/Fortify/**"
  - "app/Http/OperationGate.php"
  - "app/Http/UrlRecords.php"
  - "app/Jobs/**"
  - "routes/console.php"
---

# ENTRY>ACT: Entry points → operations

From: CTRL (Controllers), FORT (Fortify adapters), CMD (Console commands), JOB (Schedule and jobs)  
To: ACT (Operations (Actions))

This card applies to every file at either end. Every rule here applies in full; the tag says who guarantees it.

## Carries

- **ENTRY>ACT-1** Checked input values, the records the route found, the signed-in user; the operation part hands over exactly these. `part`

## Shape

- **ENTRY>ACT-2** Plain values (arrays, models, enums), not the request itself. `machine` (PHPStan: disallowedNamespaces for the request classes and request helpers in app/Actions)

## Before it passes

- **ENTRY>ACT-3** The shape of the input is checked and the Policy is checked; the operation part always does it in this order. `part`

## Must not

- **ENTRY>ACT-4** Call more than one Action for one write; make business checks in the entry point. (The operation part runs exactly one operation and makes no business check itself.) `part`

## Also applies here

- **ACT-6** (from ACT) When it succeeds: perform() returns only what a caller in this change or after() uses (the record created, the number of records handled); otherwise null. `reviewer`
