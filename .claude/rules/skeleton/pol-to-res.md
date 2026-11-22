---
paths:
  - "app/Http/Resources/**"
  - "app/Policies/**"
---

# POL>RES: Policies → Resources (can flags)

From: POL (Policies)  
To: RES (Resources)

This card applies to every file at either end. Every rule here applies in full; the tag says who guarantees it.

## Carries

- **POL>RES-1** The Policy's answer together with the operation's refusal, which become the Resource's can flag. `note`

## Proof

- **POL>RES-2** The can flag and pressing the button give the same answer in every case: the can flag asks OperationGate, which checks the same Policy ability and refusal reasons. `part`
