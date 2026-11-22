---
paths:
  - "app/Actions/**"
  - "app/Actions/OutsideCall.php"
  - "config/services.php"
---

# ACT>EXT: Operations → outside services

From: ACT (Operations (Actions))  
To: EXT (Outside services)

This card applies to every file at either end. Every rule here applies in full; the tag says who guarantees it.

## Carries

- **ACT>EXT-1** Only Actions call outside services, with the HTTP Client. `machine` (PHPStan: disallowedNamespaces Illuminate\Support\Facades\Http (allowIn app/Actions/OutsideCall.php) and App\Actions\OutsideCall (allowIn app/Actions, app/Jobs/OperationJob.php))

## Before it passes

- **ACT>EXT-2** A call that changes something on the other side happens after the transaction is committed; a call that only reads happens before the transaction starts (the outside-service part). `part`

## Must not

- **ACT>EXT-3** Call inside a transaction; call without a time limit, or let a failure pass without an error (the outside-service part). `part`
