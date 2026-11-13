# Frontend design v2, batch 1 feedback 4: a tidy column menu

## Purpose
Make the column header menu read as one neat menu: every choice is a row of the same height starting on the same left edge, with thin lines between the groups, instead of menu rows, bordered buttons and a wide button mixed together.

## Agreed decisions
From the user's fourth review (a screenshot of the Status column menu: left edges not aligned, tall sort rows and cramped tick rows, menu rows mixed with bordered buttons and a full-width Clear button, nothing separating the parts) and grilling round 9 (Q65), 2026-11-13:
1. Q65: the menu is one column of rows, like a dropdown menu. The popover uses the dropdown menu's padding (`p-1`) and every choice is one row with the `DropdownMenuItem` look: the same height and padding (`px-2 py-1.5`, `text-sm`, `gap-2`, `rounded-sm`), a 16px icon slot on the left, hover `bg-accent`, press `bg-accent-pressed`. The rows, in order: "Sort ascending" (`ArrowUp`), "Sort descending" (`ArrowDown`); "Select all" (`ListChecks`) and "Unselect all" (`ListX`), each on its own row; one row per option of the tick list (its checkbox in the icon slot, the whole row clickable); "Clear" (`X`) as the last row. No bordered or full-width button is left in the menu.
2. Q65: thin `Separator` lines, edge to edge, divide the groups: sort | the column's filter | Clear. In a tick list a line also divides "Select all" and "Unselect all" from the options.
3. The text menu and the date or amount menu use the same layout: the search box, and the From and To inputs, sit in rows with the same left padding as the other rows. From and To are one row each, the label on the left of its input (Q17), so the menu stays one column.
4. Consequence, raised to the user with the plan: the sort row in use shows the selected colour (`bg-accent-pressed`, the foundations.md rule for a selected toggle), so the current order is visible inside the menu; today it is marked only for screen readers (`aria-pressed`).
5. Consequence, raised to the user with the plan: the phone "Sort & filter" sheet shows the same `ColumnMenuContent` for each column (without Clear: the sheet keeps its one Clear in the footer), so its sections get the same rows and lines.
6. Accessible names stay as they are: the buttons "Sort ascending", "Sort descending", "Select all", "Unselect all" and "Clear"; each checkbox named by its option; the search box "Search <column>"; the labels "From" and "To". No existing assertion changes.

## Milestones
- [x] M1 Spec: `docs/design/patterns/list.md`, "Column menus" (one column of rows with the dropdown item look, the order of the rows, the lines between groups, the sort row in use in the selected colour) and "Phone" (the sheet's sections use the same rows); the `ColumnMenu` row in `docs/design/components.md` if its wording no longer matches. + DocsTest.
- [x] M2 Menu layout: `components/shared/column-menu.tsx` (`ColumnMenuContent`, `TickList`, `RangeEnd`, the `PopoverContent` padding) as decisions 1 to 5, with one row style shared by every row rather than a copy per row; `components/shared/sort-filter-sheet.tsx` only if its section spacing needs it. Move the doc comment of `isFiltered`, which sits above `sortState` today. + Vitest in `tests/js/components/shared/column-menu.test.tsx`: every choice in an open menu is a row with the same marker (`data-slot="column-menu-row"`); separators divide the groups; the sort row in use has the selected state; no `outline` button is left in the menu; the existing tests pass unchanged.
- [ ] M3 Checks, review and report: full `composer check` and `composer check:reference`; Playwright at 1280px and 390px, light and dark: the Title (text), Status (tick list), Amount and Due date (range) menus on /style and the menus of the reference task list, open, with hover and press on a row; the phone sheet at 390px; CHANGELOG entry; reviewer rounds (at most 3); report; archive this plan.

## Validation
- Per milestone: `composer check:milestone -- <affected test paths>` passes; record the scope and the reason.
- M1: `tests/Structure/DocsTest.php`.
- M2: `tests/js/components/shared/column-menu.test.tsx`, existing tests unchanged plus the new ones named in M2.
- UI: in each of the three kinds of menu, every row has the same height and every row's icon starts at the same x position (measured with `getBoundingClientRect` and recorded in Progress); hover is grey and press one step darker; the sort row in use has the selected colour; the phone sheet has no sideways scroll at 390px. Every changed page opened with the isolated headless Playwright MCP at 1280px and 390px, light and dark.
- Final implementation: full `composer check` and `composer check:reference` pass, repeated after any later code change. No package is added or upgraded (`lucide-react` already has `ListChecks`, `ListX` and `X`).

## Progress
- 2026-11-13 23:20 MYT: The user reviewed the column menu a fourth time; grilling round 9 (Q65) is answered on the user's Q&A page and recorded above. Feedback 3 is archived (final `composer check` and `composer check:reference` passed, 2 review rounds). Plan written; waiting for the user to say start.
- 2026-11-13: The user said start. M1 (spec) done: list.md "Column menus" and "Phone", components.md row. 0 tests failed before (docs only). `composer check:milestone -- tests/Structure/DocsTest.php`: Pint, PHPStan, vp check, tsc and 4 Pest tests passed (docs-only scope, DocsTest covers the docs).

- 2026-11-13 23:20 MYT: M2 done. 10 new Vitest tests failed before the code (the 16 existing ones passed); after it 26 pass, existing tests unchanged. `column-menu.tsx`: one `rowClass` shared by `MenuRow` (buttons), the tick-list `label` rows and `InputRow` (search box, From, To); `MenuLine` (`Separator`, `-mx-1`) between the groups; popover `p-1`; sort row in use `bg-accent-pressed`. `sort-filter-sheet.tsx`: each section's menu wrapped in `px-1` so the lines reach the section edges. `isFiltered` doc comment moved. Command: `composer check:milestone -- tests/js/components/shared tests/js/press-states.test.ts tests/js/class-names.test.ts tests/Structure/DocsTest.php`: Pint, PHPStan, vp check, tsc, 85 Vitest and 4 Pest passed. Scope: every Vitest file that renders the menu or checks class names, plus DocsTest; no PHP changed.

- 2026-11-13 23:30 MYT: M3 checks. Full `composer check` passed (359 Pest, 141 Vitest, Pint, PHPStan, vp check, tsc) and `composer check:reference` passed (460 Pest, 141 Vitest). UI, isolated headless Playwright, /style: at 1280px the Status (tick list) menu has 7 choice rows, all 32px high, left edge x=591, icon left x=599 and 16px wide, lines 1px from each border edge (edge to edge); the Title, Amount and Due date menus have their icons at the same x as the sort rows (Due date: 951 and 959). Input rows (search, From, To) are 40px high (32px input plus py-1), not 32px: this is the one place where row heights differ, recorded here; their left padding is the same (input starts at x+8). Light: hover grey, sort row in use `oklch(0.9 0 0)` with `aria-pressed=true`, the other row transparent. Dark (Status menu): rest transparent, hover `oklch(0.3 0 0)`, press `oklch(0.24 0 0)`, one step darker than hover as in the other press states. 390px phone sheet: page and sheet scroll width equal the window (390), no sideways scroll; its rows are 32px and have the same lines. The reference task list shares `ColumnMenu`, so it was covered through the /style list demo, not opened separately. Browser closed.
- 2026-11-13: review round 1: 0 P0, 2 P1. P1 a (the tick-list check mark turned grey on the blue fill because the muted-icon selector sat in the shared `rowClass`): fixed, the selector now sits only in `MenuRow`; a test checks that a tick row does not carry it. `composer check:milestone -- tests/js/components/shared tests/js/press-states.test.ts` passed. P1 b (`className="h-8"` on the Input in the search, From and To rows resizes a component, which the `ui` rule in principle 10 forbids, and leaves those rows 40px instead of 32px): open, put to the user as Q66; no code changed for it yet.

## Surprises & Discoveries
- `ColumnMenuContent` is shared by the header popover and the phone sheet (`clearable={false}` there), so the phone sheet changes with it (decision 5).
- The menu uses `PopoverContent`'s default `p-4` and `w-72`; the sort rows are ghost `Button`s, the tick rows have no height of their own, "Select all" and "Unselect all" are small `outline` buttons and "Clear" is a full-width `outline` button. This is why the left edges and the row heights differ.
- The sort row in use is marked only with `aria-pressed`; no style reads it.
- The doc comment of `isFiltered` sits above `sortState`.

## Decision Log
- 2026-11-13: Work continues on the branch `frontend-design-v2-batch-1`, not merged yet, so batch 1 and the four feedback rounds are merged together.
- 2026-11-13: The user said "开始"; this approves the spec edits the plan lists in docs/design/patterns/list.md and docs/design/components.md (M1).

## Outcomes & Retrospective
Not started.
