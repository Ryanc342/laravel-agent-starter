---
paths:
  - "app/Jobs/**"
  - "routes/console.php"
---

# JOB: Schedule and jobs

Location: `app/Jobs/ (part: OperationJob); the schedule in routes/console.php`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Location

- **JOB-1** Jobs live in app/Jobs/, the schedule in routes/console.php; Jobs is added to the list of app/ folders. `machine` (Structure: FoldersTest, PlacementChecks::appFolders())

## Does

- **JOB-2** Runs one business operation on a schedule or in the background: it calls only one Action. (The job part.) `part`

## Must not

- **JOB-3** Retry automatically after a failure, unless the operation is marked as harmless to run twice. (The job part's default.) `part`

## Also applies here

- **ACT-11** (from ACT) When it loops over the rows of a table that grows with use, it reads them with chunkById() or lazyById(); ->get() or ->all() before a loop only on a query capped with limit(). `reviewer`
- **CMD-2** (from CMD) Success and every kind of failure have tests. `reviewer`

## Arrows

- ENTRY>ACT: Entry points → operations ([card](./entry-to-act.md))
