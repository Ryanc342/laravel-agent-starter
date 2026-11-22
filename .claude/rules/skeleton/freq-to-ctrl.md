---
paths:
  - "app/Http/Controllers/**"
  - "app/Http/OperationGate.php"
  - "app/Http/Requests/**"
  - "app/Http/UrlRecords.php"
---

# FREQ>CTRL: FormRequests → controllers

From: FREQ (FormRequests)  
To: CTRL (Controllers)

This card applies to every file at either end. Every rule here applies in full; the tag says who guarantees it.

## Carries

- **FREQ>CTRL-1** Checked input. `note`

## Shape

- **FREQ>CTRL-2** A controller reads input only through validated(), safe() or the FormRequest's methods that read checked values (the operation part hands the Action only checked input). `reviewer`
  - Check: Look at: how controllers read input. Break: $request->input(), ->all(), ->get(), ->query() or request() for a value a FormRequest checks. Not: validated(), safe(), the FormRequest's own methods for checked values. Cite: the line.
