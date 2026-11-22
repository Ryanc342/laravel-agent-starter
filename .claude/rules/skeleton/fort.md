---
paths:
  - "app/Http/Fortify/**"
---

# FORT: Fortify adapters

Location: `app/Http/Fortify/`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Does

- **FORT-1** Takes the requests of the login package Fortify; like a controller, it checks the input and calls one Action. `note`

## Must

- **FORT-2** Validates input with Validator::make(), because Fortify passes an array, not a request; a field that has a rule in app/Concerns uses that rule. `reviewer`
  - Check: Look at: app/Http/Fortify. Break: $input used before Validator::make(...)->validate(); an inline rule where an app/Concerns trait has it; validation through request() or a FormRequest. Not: Validator::make($input, [...$this->profileRules(), 'password' => $this->passwordRules()])->validate(). Cite: the line.

## Arrows

- CONC>FREQ: Shared validation rules → FormRequests and Fortify adapters ([card](./conc-to-freq.md))
- ENTRY>POL: Entry points → Policies ([card](./entry-to-pol.md))
- ENTRY>ACT: Entry points → operations ([card](./entry-to-act.md))
