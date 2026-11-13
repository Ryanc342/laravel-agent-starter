# Frontend design v2, batch 1 feedback 3: grey hover, press feedback, visible sortable headers

## Purpose
Apply the user's third review of batch 1 before it is merged: a neutral grey instead of light blue on hover, the same press feedback on everything clickable, and column headers that show they can be clicked before the mouse reaches them.

## Agreed decisions
From the user's third review and grilling round 8 (Q62 to Q64), 2026-11-13:
1. Q62: hovering over something clickable turns it light grey, not light blue: outline and ghost buttons, menu and select items, column headers and the sidebar. Dark mode uses the matching dark grey. This is the `accent` token (and `sidebar-accent`) in `resources/css/app.css`.
2. Q63: pressing something clickable makes it one step darker than its hover colour, everywhere: every button variant (the blue primary becomes a darker blue), menu and select items, toggles, column headers, notification rows, quick links and sidebar items. A link's text gets darker. Nothing moves or changes size.
3. Q64: a column header that can be clicked always shows a faint grey up-down arrow next to its name; a header that cannot be clicked shows nothing. Sorted, the arrow becomes up or down and turns blue; filtered, a blue funnel shows (decision 6 of feedback 2, Q58, unchanged).
4. Consequence of decisions 1 and 2, raised to the user with the plan: a selected toggle and the sidebar's current page used the same colour as hover. They now use the press colour, one step darker than hover, so hover and selected stay distinguishable.

## Milestones
- [x] M1 Spec and rules: `docs/design/foundations.md` (hover, press and selected colours), `docs/design/components.md` (every clickable element has a hover and a press state; the column header arrow), `docs/design/patterns/list.md` (clickable headers show the arrow at rest), principle 10 in `docs/golden-principles.md`, DESIGN.md where it names these. + DocsTest.
- [x] M2 Colours and press states: neutral grey `accent` and `sidebar-accent` in light and dark, plus one darker step for press and selected, defined once in `resources/css/app.css`; `active:` styles on every variant in `components/ui/button.tsx`, on menu, select, toggle and sidebar items, and on the shared clickable blocks (column header, notification row, quick links); selected toggles and the sidebar's current page use the darker step. + Vitest where a class decides behaviour; screenshots at rest, hover and pressed.
- [x] M3 Column header arrow: `ColumnMenu`'s trigger always shows the faint up-down arrow; sorted shows up or down in blue; filtered adds the blue funnel; a plain header shows no icon. + Vitest; `composer check:reference` (the reference task list uses it).
- [ ] M4 Checks, review and report: full `composer check` and `composer check:reference`; Playwright at 1280px and 390px, light and dark: hover and pressed on each button variant, a menu, a toggle group, the sidebar, a column header and a notification row; CHANGELOG entry; reviewer rounds (at most 3); report; archive this plan.

## Validation
- Per milestone: `composer check:milestone -- <affected test paths>` passes; record the scope and the reason.
- M1: `tests/Structure/DocsTest.php`.
- M2: a search recorded in Progress finds an `active:` state on every button variant and every clickable shared block; in the browser, hover is grey (not blue) in light and dark, pressed is one step darker, and a selected toggle differs from a hovered one.
- M3: Vitest: a sortable header shows the up-down arrow at rest; sorted shows the direction in blue; filtered shows the funnel; a plain header has no icon.
- UI: every changed page opened with the isolated headless Playwright MCP at 1280px and 390px, light and dark.
- Final implementation: full `composer check` and `composer check:reference` pass, repeated after any later code change. No package is added or upgraded.

## Progress
- 2026-11-13 18:00 MYT: The user reviewed the screens a third time; grilling round 8 (Q62 to Q64) is answered on the user's Q&A page and recorded above. Plan written. The user said start and approved the protected files (Decision Log).
- 2026-11-13: M1 done. Updated foundations.md (grey hover, `-pressed` tokens, press rule), components.md (hover and press on every clickable, arrow at rest), patterns/list.md (clickable headers show the arrow), principle 10 and DESIGN.md. `composer check:milestone -- tests/Structure/DocsTest.php` passed (static checks plus 4 DocsTest tests); scope: docs only, no code.
- 2026-11-13: M2 and M3 done. `resources/css/app.css` has the neutral grey `accent` and `sidebar-accent` and the five `-pressed` tokens, light and dark (a darker press step in dark mode too). `active:` search (`grep -rlE "active:(bg|text)-" resources/js`): text-link, ui/button (all six variants), ui/select, ui/dropdown-menu (4 item kinds), ui/sidebar (menu button, sub button, two actions), ui/toggle, shared/feed-row, shared/quick-links; the column header is a ghost `Button`, so it has one. Badge links and navigation-menu (not used by a screen) are left as they were. `FeedRow`'s clickable button and `QuickLinks` now hover `accent`. `ColumnMenu` shows `ArrowUpDown` (`data-slot="sortable-indicator"`) at rest. New tests: `tests/js/press-states.test.ts` (3) and 3 in `tests/js/components/shared/column-menu.test.tsx` (no existing assertion changed). `composer check:milestone` on tests/js passed (98 Vitest tests).
- 2026-11-13: Browser check (isolated headless Playwright, own server on 8123 since 8000 and 5173 belong to another project, stopped after): computed colours at rest, hover and press for all six button variants, ghost, the sidebar, quick links and a toggle group in light and dark: hover `oklch(0.95 0 0)` (light) / `0.3` (dark), press `0.9` / `0.24`; primary press `oklch(0.46 0.22 263)`; a selected toggle (press colour) differs from a hovered one; the column headers on /style show the arrow; dashboard at 390px in dark has no sideways scroll. Not seen in the browser: a notification row's hover and press (the local account has no unread notification; it uses the same classes as quick links).
- 2026-11-13: Review round 1 (reviewer): P1 for clickable elements without a press state. Fixed: `BreadcrumbLink` and the show/hide password button (text goes to `primary-pressed`), the dialog and sheet close buttons (`active:bg-accent-pressed`), the passkey Remove button (a darker red tint, `active:bg-destructive/20`, instead of the ghost grey), and `FeedRow`'s button no longer reacts while disabled. Left as they were, on purpose: `Checkbox` and the `Select` trigger (form fields that show their own checked and open state, not buttons with a hover colour), badge links and `navigation-menu` (no screen uses them), and the welcome page (Laravel's own start page, replaced by the project).
- 2026-11-13: Review round 2: round 1 fixes confirmed; two new P1 fixed: list title links (list demo, reference task table) and the /style section links get `active:text-primary-pressed`; the settings side navigation's current page uses `bg-accent-pressed` (it was `bg-muted`, lighter than hover). Checkbox, Select trigger, badge links, navigation-menu and the welcome page stay unchanged (accepted by the reviewer).

## Surprises & Discoveries
- The hover colour is the `accent` token, `oklch(0.955 0.02 262)` in light mode, a light blue; `sidebar-accent` is slightly blue too. Changing the two tokens changes every component that uses them.
- No file in `resources/js` has an `active:` style, so nothing gives press feedback today.
- A selected toggle (`data-[state=on]:bg-accent`) and the sidebar's current page (`data-[active=true]:bg-sidebar-accent`) use the hover colour (decision 4).
- `ColumnMenu`'s trigger is a ghost button that shows only the header text until a sort or filter is in use.

## Decision Log
- 2026-11-13: Work continues on the branch `frontend-design-v2-batch-1`, not merged yet, so batch 1 and the three feedback rounds are merged together. The user confirms this at start.
- 2026-11-13: The user said "开始" (start): work stays on the branch `frontend-design-v2-batch-1`, and the protected files this plan changes are approved: DESIGN.md, docs/design/foundations.md, docs/design/components.md, docs/design/patterns/list.md and docs/golden-principles.md. Ask before touching any other protected file or any existing assertion.
- 2026-11-13: After review round 2 the user approved one line in the protected file `docs/reference/tasks/resources/js/components/tasks/task-table.tsx`: the title link gets `active:text-primary-pressed`, like the demo in `components/style/list-demo.tsx`.
- 2026-11-13: The darker press step is five tokens in `resources/css/app.css`: `primary-pressed`, `secondary-pressed`, `destructive-pressed`, `accent-pressed`, `sidebar-accent-pressed` (named in foundations.md).

## Outcomes & Retrospective
To be filled in at the end.
