---
paths:
  - "app/Actions/**"
  - "app/Support/StoredFile.php"
  - "config/filesystems.php"
---

# ACT>FILES: Operations → file storage

From: ACT (Operations (Actions))  
To: FILES (File storage)

This card applies to every file at either end. Every rule here applies in full; the tag says who guarantees it.

## Shape

- **ACT>FILES-1** Files are stored under a name the system generates, never the name the user gave (the file part). `part`

## Before it passes

- **ACT>FILES-2** Files users upload are stored on private storage (local) and are downloaded only through an entry point that checks the Policy; public storage holds only files meant for everyone (the file part). `part`

## Must not

- **ACT>FILES-3** Fail to store a file without an error (the file part; the storage settings also turn a failure into an error). `part`
