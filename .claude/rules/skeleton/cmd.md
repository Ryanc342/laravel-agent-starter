---
paths:
  - "app/Console/**"
---

# CMD: Console commands

Location: `app/Console/`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Does

- **CMD-1** The command-line entry point; to write data it calls run() on one Action class (once, or once per record of a batch), or runBySystem() for an operation the system does, and writes nothing itself. `reviewer`
  - Check: Look at: handle() in app/Console. Break: it writes itself (create, save, update, delete, DB::, notify, Storage::put, an outside call); two operations for one write. Not: reads, counts, output, prompts; run() or runBySystem() on one Action. Cite: the write line.

## Proof

- **CMD-2** Success and every kind of failure have tests. `reviewer`
  - Check: Look at: each new or changed command and tests. Break: success, or a kind of failure the command can meet, without a test. Not: a failure the Action's own tests cover. Cite: the command and the missing case.

## Also applies here

- **ACT-11** (from ACT) When it loops over the rows of a table that grows with use, it reads them with chunkById() or lazyById(); ->get() or ->all() before a loop only on a query capped with limit(). `reviewer`

## Arrows

- ENTRY>ACT: Entry points → operations ([card](./entry-to-act.md))
