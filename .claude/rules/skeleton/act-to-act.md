---
paths:
  - "app/Actions/**"
---

# ACT>ACT: Operation → operation

From: ACT (Operations (Actions))  
To: ACT (Operations (Actions))

This card applies to every file at either end. Every rule here applies in full; the tag says who guarantees it.

## Carries

- **ACT>ACT-1** When one operation needs another, it calls that operation's Action directly. `reviewer`
  - Check: Look at: Actions that need another operation. Break: the other operation's writes repeated, or its model methods called, instead of its Action's run(). Not: run() on the other Action. Cite: the repeated lines.

## Before it passes

- **ACT>ACT-2** The called operation is called through run(), and its writes are in the same transaction as the caller's (the operation part); a call that changes something in an outside service happens only after the outermost transaction is committed (the outside-service part). `part`
