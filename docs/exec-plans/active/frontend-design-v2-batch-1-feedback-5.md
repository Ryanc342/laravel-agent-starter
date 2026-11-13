# Frontend design v2, batch 1 feedback 5: quiet field focus

## Purpose
Make form fields quiet: no glow around a field when it is clicked into or has an error, only its border changes, so the forms match the flat, clean style the user chose.

## Agreed decisions
From the user's fifth review (a screenshot of the /style inputs: the user does not like the blue and red highlight) and grilling round 11 (Q67), 2026-11-13. Q67 was shown as Q66 on the user's Q&A page and is renumbered here because feedback 4 already used Q66 for the input size.
1. Q67: a form field has no glow. Clicked into (focus), its border turns dark grey; with an error, its border is red, and it stays red with no glow when clicked into. The dark grey is a new token `input-focus`, defined once in `resources/css/app.css`: light `oklch(0.5 0.025 265)`, dark `oklch(0.72 0.02 265)` (the lightness of `muted-foreground`), so the focused border stays clearly visible against the page (golden principle: keyboard focus stays visible).
2. Q67 scope: the fields `Input` (both sizes), the `Select` trigger, `Checkbox` and the `InputOTP` slot (its active slot's line uses `input-focus` instead of `ring`). Buttons, toggles and badges keep their blue keyboard ring: it shows only when moving with the Tab key, not on a mouse click. The `ring` token stays `--primary` for them.

## Milestones
- [x] M1 Spec: `docs/design/foundations.md` (the `input-focus` token; the focus ring that follows `--primary` is the keyboard ring of buttons, toggles and badges, not of fields); `docs/design/components.md` (a field's focus and error look). + DocsTest.
- [x] M2 Quiet field focus: the `input-focus` token (light and dark) in `resources/css/app.css`; `components/ui/input.tsx`, `select.tsx`, `checkbox.tsx` and `input-otp.tsx` as decisions 1 and 2: no `ring-[3px]` and no `aria-invalid:ring-*` on a field, focus border `input-focus`, error border `destructive` also while focused. + Vitest guard: no field in `components/ui` has a focus or error ring; buttons keep theirs.
- [ ] M3 Checks, review and report: full `composer check` and `composer check:reference`; Playwright at 1280px and 390px, light and dark: on /style and the login page, an input, a select and a checkbox at rest, focused, with an error and with an error while focused; the column menu's small inputs; the two-factor code input; a button focused with the Tab key still shows its ring; CHANGELOG entry; reviewer rounds (at most 3); report; archive this plan.

## Validation
- Per milestone: `composer check:milestone -- <affected test paths>` passes; record the scope and the reason.
- M1: `tests/Structure/DocsTest.php`.
- M2: the Vitest guard named in M2; existing tests unchanged.
- UI: the computed `box-shadow` of a focused input, select and checkbox, and of an input with an error, is `none`; a focused input's border colour is `input-focus`; an input with an error keeps the `destructive` border while focused; light and dark, recorded in Progress. Every changed page opened with the isolated headless Playwright MCP at 1280px and 390px, light and dark.
- Final implementation: full `composer check` and `composer check:reference` pass, repeated after any later code change. No package is added or upgraded.

## Progress
- 2026-11-14 00:10 MYT: The user does not like the blue and red glow on fields; grilling round 11 (Q67) is answered on the user's Q&A page and recorded above. Feedback 4 is archived (final `composer check` and `composer check:reference` passed, 2 review rounds). Plan written; waiting for the user to say start.
- 2026-11-14: The user said start. M1 done: the `input-focus` token and the field look are in `docs/design/foundations.md` and `docs/design/components.md`; `composer check:milestone -- tests/Structure/DocsTest.php` passed (static checks and the affected test; docs-only change). M2 done: `input-focus` token in `app.css`; `Input`, the `Select` trigger, `Checkbox` and the `InputOTP` slot have no ring, a `input-focus` border when focused and a `destructive` border with an error, also while focused; new Vitest guard `tests/js/field-focus.test.ts` (token, no field ring, error stays red, `Button` keeps its ring). Buttons, the password eye button and `FeedRow` keep their keyboard rings, as agreed. M2 checked with the milestone check below.

## Surprises & Discoveries
- `Input`, the `Select` trigger and `Checkbox` share the shadcn focus look: `focus-visible:border-ring` plus a 3px `ring-ring/50` glow, and with an error a red border plus a `ring-destructive/20` glow (`/40` in dark). A text input matches `:focus-visible` on a mouse click too, so the glow shows every time a field is clicked; a button's ring shows only with the keyboard. `ring` is `var(--primary)`.

## Decision Log
- 2026-11-13: Work continues on the branch `frontend-design-v2-batch-1`, not merged yet, so batch 1 and the five feedback rounds are merged together.
- 2026-11-14: The user said start. M1 changes `docs/design/foundations.md` and `docs/design/components.md`, which the plan's M1 lists.

## Outcomes & Retrospective
Not started.
