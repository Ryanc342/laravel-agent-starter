---
paths:
  - "resources/css/**"
---

# THEME: Theme

Location: `resources/css/app.css`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Does

- **THEME-1** The values of colours, fonts, radii and spacing; the pointer cursor on clickable things and the arrow on disabled ones are set here, once. `note`
- **THEME-2** app.css says what each setting is for; a project with its own brand changes --primary and the logo only. `note`

## Must

- **THEME-3** The two background colours in the HTML shell (app.blade.php) match the theme. `test` (Vitest: tests/js/theme-background.test.ts)

## Arrows

- COMP>THEME: Components → theme ([card](./comp-to-theme.md))
