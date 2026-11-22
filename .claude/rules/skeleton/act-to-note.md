---
paths:
  - "app/Actions/**"
  - "app/Notifications/**"
---

# ACT>NOTE: Operations → notifications

From: ACT (Operations (Actions))  
To: NOTE (Notifications)

This card applies to every file at either end. Every rule here applies in full; the tag says who guarantees it.

## Carries

- **ACT>NOTE-1** Messages for users; only Actions send notifications and mail. `machine` (PHPStan: disallowedNamespaces for mail and notifications (allowIn app/Actions) and disallowedMethodCalls notify() and notifyNow())
