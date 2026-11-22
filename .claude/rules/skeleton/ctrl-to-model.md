---
paths:
  - "app/Http/Controllers/**"
  - "app/Http/OperationGate.php"
  - "app/Http/UrlRecords.php"
  - "app/Models/**"
---

# CTRL>MODEL: Controllers → models (reads)

From: CTRL (Controllers)  
To: MODEL (Models)

This card applies to every file at either end. Every rule here applies in full; the tag says who guarantees it.

## Carries

- **CTRL>MODEL-1** Records read through the model's query scopes: a controller writes no where, orderBy or join itself, every condition and order is a named scope; route model binding and reading by primary key are allowed. `reviewer`
  - Check: Look at: queries in app/Http/Controllers. Break: where(), orderBy(), join() or a raw condition written in the controller. Not: a named scope; route model binding; find() by primary key. Cite: the line.

## Before it passes

- **CTRL>MODEL-2** A list that keeps growing is paginated before it is sent to the page; the listing part always paginates. `part`
- **CTRL>MODEL-3** A list starts from the model's "records this user can see" query scope (visibleTo); the listing part always starts from it, and fails when the model has none. `part`
