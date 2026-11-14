# Frontend design v2, batch 3 feedback 2: rules without exceptions

## Purpose
After a full audit of every rule, check and spec file changed since the UI work began, make the rules agree with each other and with the template's first intent (values in one place, machines check what they can, the demo an AI copies is always right), and turn every written exception into part of the template: a shared component or one general rule that covers the case, so no "except" is left for an AI to remember.

## Agreed decisions
From the user, 2026-11-14: the open items of the batch 3 feedback run (in chat), the rules audit (grilling rounds 17 and 18, Q103 to Q117, on the Q&A page; where a question was asked twice, round 18's answer counts) and the user's principle "an exception is a last resort; make it part of the template":

Open items of the last run
1. The language switch is authorised like every controller: the template keeps every permission in a Policy and has no `Gate::define`, so the users' policy (`app/Policies/UserPolicy.php`; create it if there is none) gets `updateLocale(?User $user): bool`, which always allows, guests included, and `LocaleController::update` calls `Gate::authorize('updateLocale', User::class)` before the Action.
2. Every form sets `noValidate`, so the browser never shows its own bubble in the browser's language; an empty required field reaches the server, whose message comes back in the app's language (toast, red border, jump to the field). `required` stays on inputs so screen readers still announce it. Principle 4's line on HTML attributes says so.

Conflicts made consistent
3. Q103: a row of number tiles is one shared block, `StatTiles` (backend numbers only, no placeholders). A report page has it; a dashboard has it when its plan asks for it. DESIGN.md, principle 10 and `patterns/report.md` / `dashboard.md` say the same.
4. Q104: a frontend test checks what a user does or hears (clicks, keys, focus, the URL, the request sent, roles and names, translated text), never which class a component carries. Tests that only check class names or class strings are removed; tests that mix keep their behaviour checks. How a screen looks is guarded by the shared components, by lint rules (decision 12) and by the browser screenshots each plan takes. `docs/rules/tests.md` says which frontend code gets tests: a component or hook with behaviour; a component that only lays out markup gets none.
5. Q105: one rule for where a form lives: creating or editing a record that the user opened from a list is a `ResponsiveDialog`; a page whose purpose is the form (sign-in, settings, contact, checkout, a wizard) is that page. No "one exception" wording remains in principle 10, `form.md` or `wizard.md`.
6. Q106: every list page reads `per_page` through its FormRequest with `ReadsPerPage` (10, 20, 50 or 100; anything else 20) and shows the rows-per-page choice; "unless the plan agreed another size" and "a list that offers a rows-per-page choice" go from principle 3 and `docs/rules/controllers.md`.
7. Q107: after login the user lands on the dashboard; unchanged.
8. Q108: "today" comes from the backend: a shared prop with the date in the app's timezone (and its year), used by `MonthCalendar`, the `DatePicker`'s today mark and the public footer's year. Laying out a month (the grid, the previous and next month) is display and lives only in `lib/month-grid.ts` and `lib/date-value.ts`, which keep their tests; any date calculation that decides something (overdue, due, eligible) stays in the backend. Principle 4's date line says so, and no `new Date()` or `Date.now()` is left in `resources/js` outside those two files and `lib/format.ts` reading a value it was given.
9. Small fix: DESIGN.md's list of what may float with a shadow names the toast, as principle 10 does.

Back to the first intent
10. Q109: the spec names tokens and component sizes, never values: every pixel size and Tailwind size class in DESIGN.md and `docs/design/` (foundations.md, components.md, list.md, product.md, pricing.md, landing.md and any other) becomes the token or the component's `size` that holds it. A DocsTest check fails on a pixel value or a Tailwind size class in those files.
11. Q111: /style is translated like every page; the lint exemption for `components/style/` and `pages/style/` and the review rule's "/style tool's own labels" go. Example data a demo receives counts as user content and is not translated, as for any page.
12. Q110: the UI rules a machine can see in the code become `[oxlint]` rules in `lint/oxlint/ezdev.js`, each shown failing once on a deliberate example before the code is cleaned: a Tailwind palette colour (`bg-blue-500`) or a hex colour in a class or style; an arbitrary value in a class (`text-[13px]`); a `dark:` colour override; `type="date"`; an `h-*` or `p-*` class on a `components/ui` component (its `size` variant instead); an icon-only `Button` without `aria-label`; a `<Form>` or `<form>` without `noValidate` (decision 2); the display classes outside the two blocks that own them (decision 14). Principle 10 tags these rules `[oxlint]`; rules only a screen shows stay `[review]`.

Exceptions turned into the template
13. Q113: `FormField` decides from its own width: the label sits to the left of the input when the form is wide, above it when the form is narrow (a phone, the sign-in column, a narrow dialog), by a container query on the form; the `stacked` prop goes. The rule is one sentence with no page named.
14. Q114: the display sizes belong to the two blocks that use them, `Hero` and `PricingPlans`; a page cannot pick a heading size (`Heading` has its variants only), and the pricing demo stops using the display class itself. The rule names the blocks, not pages.
15. Q115: a sentence under a title has fixed places only: a `ConfirmDialog` for something that cannot be undone has a `consequence`, and a step the user must take (a wizard step, a two-factor step) has an `instruction`. `ResponsiveDialog` loses its `description`; the seven files that pass one move to the slot that fits.
16. Q116: every row of action buttons is one shared block, `Actions` (`components/shared`): at the right end of its row, the main button last, and full width and stacked with the main button on top when its container is narrow. `ResponsiveDialogFooter`, `WizardLayout`'s footer, record headers, toolbars, settings forms and the single section buttons (Security, Delete account) use it. The list of "these keep their own place" goes: a component that is not `Actions` places its own buttons.
17. Q112: the /style demo frame is a light tinted surface with no border, so "no bordered box directly inside another" needs no exception.
18. Q117: any burst of errors shows at most five toasts plus one "And N more errors", forms and imports alike: the cap moves into `useValidationErrorToasts`, and the import request stops cutting its own list (`SHOWN_ERRORS`, `more_errors`) and returns every row error.

Tests
19. Existing assertions change only where these decisions require it (decision 4's class-only tests, decision 18's import errors, and the props that decisions 13, 15 and 16 remove or rename); every changed or removed assertion is listed in Progress. Any other assertion that would change: stop and ask the user.

## Milestones
- [x] M1 Rules and spec: decisions 2 to 18 in `docs/golden-principles.md`, DESIGN.md, `docs/design/` (foundations, components and the pattern files they touch), `docs/rules/tests.md`, `frontend.md`, `controllers.md`, `translations.md` and `visual-design.md`; decision 10's DocsTest check, seen failing once. + DocsTest.
- [ ] M2 Shared blocks that own the rules: `Actions` (16), `ConfirmDialog` and the step `instruction` (15), width-driven `FormField` (13), `StatTiles` (3), the display classes inside `Hero` and `PricingPlans` only (14), the tinted demo frame (17), the toast cap (18); every use moved to them, in `resources/js` and `docs/reference/tasks/resources/js`. + Vitest for behaviour (decision 4); `composer check:reference`.
- [ ] M3 Today from the backend (decision 8). + Pest for the shared prop; Vitest for the calendar's today mark with a given date.
- [ ] M4 Backend and forms: decision 1 (+ Pest: guest and user may switch; a denying policy fails the request), decision 6 (every list FormRequest), decision 18's import request (+ its Pest tests), decision 2's `noValidate` on every form (27 files hold one today).
- [ ] M5 Lint rules (decision 12) and the /style exemption removed (decision 11), each rule seen failing once, then the code cleaned until `composer check` passes.
- [ ] M6 /style in Chinese (decision 11): every label through `t()`, its Chinese in `lang/zh_CN.json`; `TranslationsTest` passes.
- [ ] M7 Tests (decision 4): remove the class-only tests and assertions, keep the behaviour ones; list each file and what was removed in Progress.
- [ ] M8 Checks: full `composer check` and `composer check:reference`; isolated headless Playwright at 1280px and 390px, light and dark, English and Chinese: the sign-in page, a task dialog and settings (labels beside or above by width), every action row (task page, dialogs, wizard, print, settings, Security, Delete account), a confirm dialog's consequence, a form submitted with more than five errors and a bad import (five toasts and "And N more errors"), the calendar's today, `/style` in Chinese with its tinted frames, the stat tiles on the report demo.
- [ ] M9 Docs, review and report: CHANGELOG (the unreleased 1.2.0 entry), `docs/QUALITY.md`; reviewer rounds (at most 3); report; archive this plan.

## Validation
- Per milestone: `composer check:milestone -- <affected test paths>` passes; record the scope and the reason.
- No `except`, `exception`, `exempt` or "the one place" wording is left in principle 10, DESIGN.md or `docs/design/` for a UI rule (a search recorded in Progress); the RoutesTest fallback line and pre-existing template wording outside UI rules are not UI exceptions.
- Decision 10: the DocsTest check passes and failed once on a planted value. Decision 12: each lint rule failed once on a planted example. Decision 11: no file is exempt from `ezdev/no-untranslated-text`.
- Decision 8: no `new Date()` or `Date.now()` in `resources/js` outside the two layout files and `lib/format.ts`.
- Decision 19: every changed or removed assertion is listed in Progress with its decision.
- Final implementation: full `composer check` and `composer check:reference` pass, repeated after any later code change. No package is added or upgraded.

## Progress
- 2026-11-14 20:40 MYT: The open items of the batch 3 feedback run answered in chat (decisions 1, 2 and single buttons on the right). Plan written.
- 2026-11-14 21:10 MYT: Claude audited every rule, check and spec file changed since the UI work began (47 files); grilling rounds 17 and 18 (Q103 to Q117) answered on the Q&A page; this plan rewritten to hold all of it. Waiting for the user to say start.
- 2026-11-14 22:05 MYT: The user said "开始". M1 done: decisions 2 to 18 written into docs/golden-principles.md, DESIGN.md, docs/design/ (foundations, components, 19 pattern files), docs/rules/ (tests, frontend, controllers, translations, visual-design); the DocsTest check for decision 10 was seen failing twice (first on the real leftovers `text-display`, `min-w-0`, `items[0][quantity]`, which I fixed in the check and the text; then on a planted `16px` in list.md, restored). Lint rule names written in principle 10 for M5: `ezdev/no-raw-style`, `ezdev/no-component-resize`, `ezdev/no-display-size`, `ezdev/form-no-validate`, `ezdev/icon-button-label`, `ezdev/no-date-input`. Exception-wording search over principle 10, DESIGN.md and docs/design/: no `except`, `exception`, `exempt` or "the one place" left for a UI rule (the two `unless` hits are in principle 6 and in the label rule, not exceptions). Scope: `composer check:milestone -- tests/Structure` (docs only; the structure tests read them): Pint, PHPStan, oxlint, tsc and 44 structure tests passed.

## Surprises & Discoveries
- DESIGN.md allowed stat tiles on a dashboard when the plan asks, while principle 10 and `report.md` allowed them only on a report (decision 3).
- `docs/rules/tests.md` says pages and components get no tests, while 38 component test files exist; of about 400 assertions in `tests/js`, about 150 check class names or class strings (decision 4).
- Principle 10 called the wizard "the one exception" to dialogs while also keeping settings and sign-in on their pages; checkout and contact are pages too (decision 5).
- `MonthCalendar` takes today from `new Date()`, and the public footer's year from `new Date().getFullYear()` (decision 8).
- DESIGN.md and `docs/design/` hold more than 20 pixel values and Tailwind size classes, against `visual-design.md`'s "the spec never repeats a value" (decision 10).
- 32 UI rules were `[review]` and 2 machine-checked (decision 12).
- `ezdev/no-untranslated-text` skips all of `components/style/` and `pages/style/`, the demos AI agents copy (decision 11).
- `FormField` has a `stacked` prop used in 18 places; `ResponsiveDialog`'s `description` is passed in 7 files; `pricing-demo.tsx` uses `text-display` outside `Hero` and `PricingPlans`; the import request cuts its errors at five itself (`SHOWN_ERRORS`).
- The RoutesTest fallback line is not an exception: Laravel matches the fallback route last whatever its place.

## Decision Log
- 2026-11-14: Work continues on the branch `frontend-design-v2-batch-1`; the user merges and releases only after every batch.
- 2026-11-14: Protected files this plan changes, approved when the user says start: DESIGN.md, `docs/golden-principles.md`, `docs/rules/tests.md`, `docs/rules/frontend.md`, `docs/rules/controllers.md`, `docs/rules/translations.md`, `docs/rules/visual-design.md`, every file in `docs/design/` that decisions 3 to 18 touch, `lint/oxlint/ezdev.js` and its tests in `lint/` if there are any, `tests/Structure/DocsTest.php`, `tests/Structure/TranslationsTest.php` if decision 11 needs it, and every file in `docs/reference/tasks/` that decisions 2, 6, 13, 15, 16 or 18 change.

- 2026-11-14: The user said "开始" on this plan, which is the approval its Decision Log line above records. Protected files changed in M1, by full path: DESIGN.md, docs/golden-principles.md, docs/rules/tests.md, docs/rules/frontend.md, docs/rules/controllers.md, docs/rules/translations.md, docs/rules/visual-design.md, docs/design/foundations.md, docs/design/components.md, docs/design/patterns/approval.md, docs/design/patterns/auth.md, docs/design/patterns/calendar.md, docs/design/patterns/checkout.md, docs/design/patterns/contact.md, docs/design/patterns/dashboard.md, docs/design/patterns/detail.md, docs/design/patterns/feed.md, docs/design/patterns/form.md, docs/design/patterns/import-export.md, docs/design/patterns/kanban.md, docs/design/patterns/landing.md, docs/design/patterns/list.md, docs/design/patterns/pricing.md, docs/design/patterns/print.md, docs/design/patterns/product.md, docs/design/patterns/report.md, docs/design/patterns/settings.md, docs/design/patterns/wizard.md, tests/Structure/DocsTest.php.
- 2026-11-14: The DocsTest check for decision 10 counts a pixel/rem/em value, a class with a value in square brackets, `text-xs|sm|base|lg|xl|Nxl|display(-lg)`, and `h|w|size|min-|max-` and `p|m|gap|space` classes followed by a digit. `rounded-*` (the radius tokens) and `md` (a breakpoint name) are token names and allowed. Body and caption text are named by role ("the body size", "one step smaller"), because app.css holds no token for them; the samples show the classes.

## Outcomes & Retrospective
Not started.
