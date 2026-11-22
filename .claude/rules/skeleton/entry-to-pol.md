---
paths:
  - "app/Http/Controllers/**"
  - "app/Http/Fortify/**"
  - "app/Http/OperationGate.php"
  - "app/Http/UrlRecords.php"
  - "app/Policies/**"
---

# ENTRY>POL: Entry points → Policies

From: CTRL (Controllers), FORT (Fortify adapters)  
To: POL (Policies)

This card applies to every file at either end. Every rule here applies in full; the tag says who guarantees it.

## Before it passes

- **ENTRY>POL-1** Before a record is read or changed, the operation's Policy ability is checked; the record in the URL is checked first by the operation part, the listing part and the record part, and when there is no single record yet, the class-level ability is checked. `part`
- **ENTRY>POL-2** An input field that holds a record's key is named in the operation's records(); the operation part finds the record, checks the ability on it, locks it and hands over the record, never the key. `reviewer`
  - Check: Look at: input fields that hold a record's key, and records(). Break: an id read from the input and looked up in perform() or refusal() instead of being named in records(). Not: the record in the URL; keys named in records(). Cite: the line.
