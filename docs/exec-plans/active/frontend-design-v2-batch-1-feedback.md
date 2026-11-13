# Frontend design v2, batch 1 feedback: toasts, rows per page, quieter headings and lists

## Purpose
Apply the user's review of batch 1 before it is merged: field errors as toasts that lead to the field, a rows-per-page choice under lists, no description lines under titles, tables and lists that stay visible when empty, a calmer notification list, and a detail page that names its record once.

## Agreed decisions
From the user's review of the batch 1 screens and grilling rounds 4 to 6 (Q41 to Q52), 2026-11-13:
1. Status words start with a capital letter. The backend already sends them that way (`Open`, `Done`); the style overview page shows its badge examples the same way, and `docs/design/components.md` says so.
2. Toasts appear at the top centre.
3. Q41, Q49 and the user's note: a field error is a toast, one toast per error. The field keeps only its red border, with no error text under it. After a failed submit the page or dialog scrolls smoothly to the first field with an error and puts the cursor in it; when the user asks for reduced motion it jumps there without the animation. This replaces the Q31 answer "field errors stay next to the field".
4. Q52: many toasts at once use sonner's default: the newest few are visible, the rest stack behind and spread out on hover.
5. Q42: a list offers 10, 20, 50 or 100 rows per page, default 20. The sentence in `docs/rules/frontend.md` that accepts the per-row queries because "a list page shows at most 20 rows" changes to accept them up to 100 rows.
6. Q43: the rows-per-page choice lives only in the URL (`?per_page=50`); leaving the list and coming back shows 20 again. Q51: a value that is not one of the four is treated as 20.
7. Q44: the rows-per-page choice sits under the table, next to the page links.
8. Q45: on a detail page the breadcrumb ends at the list (`Tasks`); the record's name appears once, as the page heading.
9. A table or list with no records still shows (its header row, or its box) with the empty line inside it.
10. Q46: a notification list, on the dashboard and on the notifications page, is one bordered box with its rows separated by lines. An unread row has the blue dot and darker text; no row has a grey background, and no row is a card of its own.
11. Q47 and Q50: no description line under any title (page, section, card or dialog). Two exceptions, one sentence each: a confirmation for something that cannot be undone says what will happen, and a step the user must take says what to do (the two-factor setup dialog). The warning box above "Delete account" goes; its confirmation dialog keeps its one sentence.
12. Q48: the style overview page follows the spec too: no description lines under its section titles.
13. Unchanged from batch 1 and still in force: page messages that used an Alert are toasts (Q31); delete confirmation stays a dialog.

## Milestones
- [x] M1 Spec and rules: `docs/design/components.md` (Badge text, Toast position, FormField errors, Heading and Card without description, ResponsiveTable empty row, Pagination with rows per page), `docs/design/patterns/` (form, list, detail, states, feed, dashboard, settings, auth), DESIGN.md where it names these, principle 10 in `docs/golden-principles.md`, `docs/rules/frontend.md` (rows per page, errors, breadcrumbs) and `docs/rules/controllers.md` (a list's query string, `per_page` included, goes through a FormRequest). + DocsTest.
- [x] M2 Toasts and field errors: Toaster at the top centre; `FormField` shows the red border and keeps the message only for screen readers; one shared hook turns a failed submit into one toast per error and scrolls to and focuses the first field with an error (no animation under reduced motion); every form uses it (settings, sign-in pages, two-factor, passkeys, the reference feature). + Vitest: one toast per error, scroll and focus on the first field, no smooth scroll under reduced motion.
- [ ] M3 No description lines: remove the descriptions under page, section, card and dialog titles, the auth layout description and the "Delete account" warning box; keep one sentence in the confirmations that cannot be undone (delete account, remove passkey, delete task) and in the two-factor setup steps; remove the section descriptions on the style overview page and capitalise its badge examples. + existing tests.
- [ ] M4 Empty tables and lists, and the notification list: `ResponsiveTable` keeps its header row (and on a phone its box) with the empty line inside; one bordered notification list with line-separated rows, used by the dashboard block and the notifications page; the reference feature's task table. + Vitest for the empty table and the list rows.
- [ ] M5 Rows per page: a FormRequest for the notifications list and one for the reference task list read `per_page` (10, 20, 50 or 100, anything else 20); the controllers page with it and keep the query string in the page links; `Pagination` shows the rows-per-page choice next to the page links and keeps the current filters. + feature tests (10, 50, 100, an invalid value gives 20, page links keep `per_page`), Vitest for the choice, `composer check:reference`.
- [ ] M6 Detail breadcrumb: the reference task page and the detail demo end the breadcrumb at `Tasks`. + `composer check:reference`.
- [ ] M7 Checks, review and report: full `composer check` and `composer check:reference`; Playwright at 1280px and 390px, light and dark, on every changed page; CHANGELOG entry; reviewer rounds (at most 3); report; archive this plan.

## Validation
- Per milestone: `composer check:milestone -- <affected test paths>` passes; record the scope and the reason.
- M1: `tests/Structure/DocsTest.php` (principle rules keep their two tags, every path AGENTS.md and ARCHITECTURE.md name exists).
- M2: Vitest shows one toast per error, the first field with an error scrolled into view and focused, `behavior: 'auto'` under reduced motion, and no visible error text under a field. A Playwright check submits an invalid form whose first error is below the fold and sees it scrolled into view.
- M3: a search recorded in Progress finds no `CardDescription` and no `description=` on a `Heading` outside the two exceptions.
- M4: Vitest shows an empty table with its header row and the empty line inside; the notification list renders one box.
- M5: feature tests for the notifications list and, through `composer check:reference`, the task list: `per_page` 10, 50 and 100 page by that size, `per_page=999` pages by 20, and the next-page link keeps `per_page`.
- M6: the task page's breadcrumb has one item, `Tasks`; checked on a screenshot.
- UI: every changed page opened with the isolated headless Playwright MCP at 1280px and 390px, light and dark.
- Final implementation: full `composer check` and `composer check:reference` pass, repeated after any later code change. No package is added or upgraded.

## Progress
- 2026-11-13 14:55 MYT: The user reviewed the batch 1 screens; grilling rounds 4 to 6 (Q41 to Q52) are answered on the user's Q&A page and recorded above. Plan written. Waiting for the user's start and approval of the protected files listed in the Decision Log.
- 2026-11-13: The user said "开始" (start), which confirms working on the branch and approves the protected files listed in the Decision Log. M1 done: DESIGN.md, docs/design/ (components, foundations, patterns form, list, detail, states, feed, dashboard, settings, auth), docs/golden-principles.md, docs/rules/frontend.md and docs/rules/controllers.md say what the agreed decisions say. Documentation only, so no test failed first. Scope: tests/Structure/DocsTest.php. Command: `composer check:milestone -- tests/Structure/DocsTest.php`, passed (Pint, PHPStan, frontend check, tsc, 4 Pest tests). Milestone pass, not a full-suite pass.
- 2026-11-13: M2 done. Toaster at `top-center`; `useFieldError` (resources/js/hooks/use-field-error.ts) toasts each field error once (id per field), sets `aria-invalid` on the input (red border from the shadcn styles; the OTP slots got the same), and scrolls to and focuses the first field with an error in page order (`behavior: 'auto'` under reduced motion). `FormField` calls it and keeps its message `sr-only`, so every form built from `FormField` gets it. The challenge page and the setup dialog's code boxes moved into `FormField`; passkey sign-in shows its error as a toast; the focus-on-error code in security.tsx and delete-user.tsx (and `passwordRef`) is gone because the hook does it. Vitest first: `tests/js/hooks/use-field-error.test.tsx` failed to load before the hook existed (1 file, 0 tests ran). The `form-field.test.tsx` assertion "the error shows under the input" was replaced because decision 3 changed the requirement (field errors are toasts). Scope: tests/js, tests/Structure. Command: `composer check:milestone -- tests/js tests/Structure`, passed (60 Vitest tests). Milestone pass, not a full-suite pass. The Playwright check of the below-the-fold case runs in M7.

## Surprises & Discoveries
- The backend already sends capitalised status words; only the style overview page showed lowercase badge text (it printed the variant names).
- `Pagination` hides itself when a list has one page, so the rows-per-page choice needs its own place in it, or it disappears with the page links.
- `docs/rules/controllers.md` requires a FormRequest for a list's query string, so `per_page` needs one for each list.
- The "Delete account" warning box is page text, not a confirmation dialog, so decision 11 removes it.
- Still open from batch 1 and not part of this plan: the nine items in the batch 1 plan's Outcomes ("Left for the user to decide").
- Inertia's `<Form>` clears its errors before each submit (`resetBeforeSubmit`), so the same error arriving twice re-runs the effect: a second failed submit toasts and scrolls again. To confirm in the M7 Playwright pass.

## Decision Log
- 2026-11-13: Work continues on the branch `frontend-design-v2-batch-1`, which is not merged yet, so batch 1 and its feedback are merged together. The user confirms this at start.
- 2026-11-13: Protected files this plan changes, awaiting the user's approval at start: DESIGN.md, docs/design/, docs/golden-principles.md, docs/rules/frontend.md, docs/rules/controllers.md and docs/reference/tasks/. Ask before touching any other protected file.
- 2026-11-13: The user said "开始", which approves the files above and the branch decision. docs/reference/tasks/ is changed in M5 and M6, not before.

## Outcomes & Retrospective
To be filled in at the end.
