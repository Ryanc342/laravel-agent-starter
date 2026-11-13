# Frontend design v2, batch 2 feedback: no nested box in the search demo, Chinese in PDFs, the untested batch 2 parts

## Purpose
Apply the user's review of batch 2 and the four decisions on its tech debt: the search demo stops drawing a box inside a box, a PDF prints Chinese, the kanban drag, the report range and the print sheet are checked in a real browser and fixed, a phone can select every result for a bulk action, the 404 fallback route is written plainly, and the spec says where a /style demo gets what a backend would answer.

## Agreed decisions
From the user's review of batch 2 (a screenshot of the /style search results demo: the search box sits in a bordered box inside the bordered demo frame) and grilling round 15 (Q92 to Q95), 2026-11-14:
1. Review: the search demo draws the top bar as the app draws it, a full-width strip with a line under it (like `app-sidebar-header.tsx`: `border-b`, the header's height and padding), not a rounded bordered box. The demo frame is `flush` so the strip reaches its edges, and the results page below it has the page padding. Nothing in the demo is a bordered box directly around another bordered box.
2. Consequence, raised to the user with the plan: the reviewer missed this because the `[review]` rule in principle 10 and DESIGN.md speak of a "card inside a card", and the box was a hand-drawn `rounded-lg border`, not `Card`. Both say that any bordered box counts, hand-drawn or `Card`.
3. Q92: the template ships an open-licence Chinese font for DOMPDF: Noto Sans SC Regular and Bold as static TrueType files (`.ttf`) in `resources/fonts/`, with their SIL Open Font License file. The PDF view uses it for all its text (it has Latin letters too, so no per-character fallback is needed), and DOMPDF's font subsetting is on, so a PDF embeds only the characters it uses. If static TrueType files cannot be downloaded or DOMPDF cannot read them, stop and ask the user.
4. Q93: the parts batch 2 could not check in jsdom are checked now with the isolated headless Playwright MCP and fixed where they fail: dragging a kanban card to another column with the mouse and with the keyboard, the "Move to" menu, each report range preset and Custom, and the print page in print media with no grey frame around the sheet.
5. Q93: on a phone, where the cards have no header checkbox, the floating bar offers "Select this page" as soon as one card is ticked, and then "Select all N results", as decision 14 of batch 2 gives a computer.
6. Q94: `tests/Structure/RoutesTest.php` skips Laravel's fallback route in the "a wildcard path that would catch it" test, because Laravel matches the fallback last whatever its place in the list. The fallback route then sits in `routes/web.php` as a plain `Route::fallback(...)` in the file's web routes, with the same controller and still without `HandleInertiaRequests`, instead of `app()->booted()`.
7. Q95: DESIGN.md says, next to the /style row, that a demo has no backend: what a backend would answer (a status, a validation result, an import's errors) comes with the page's example data through props.

## Milestones
- [x] M1 Spec: DESIGN.md (decisions 2 and 7), principle 10 in `docs/golden-principles.md` (decision 2), `docs/design/patterns/print.md` (the font, decision 3), `docs/design/patterns/bulk-actions.md` (decision 5). + DocsTest.
- [ ] M2 Search demo: `components/style/search-demo.tsx` and its place in `pages/style/index.tsx` as decision 1. + Vitest only if a class decides behaviour; screenshots at 1280px and 390px, light and dark.
- [ ] M3 Chinese in PDFs: the font files and licence in `resources/fonts/`; the DOMPDF font and subsetting settings; the reference PDF view (`docs/reference/tasks/resources/views/tasks/pdf.blade.php`) uses the font. + Pest: the PDF of a task titled 寄发票给供应商 embeds a Noto Sans SC font; record both font files' sizes and that PDF's size in Progress; `composer check:reference`.
- [ ] M4 Browser checks and fixes: decision 4 in the browser, each result recorded in Progress; any failure fixed with a Vitest test where jsdom can reach it; decision 5 in `BulkActionBar` and `use-row-selection.ts`. + Vitest for decision 5; `composer check:reference` (the reference task list uses the bar).
- [ ] M5 Fallback route: decision 6 in `tests/Structure/RoutesTest.php` and `routes/web.php`. + the existing signed-in and signed-out 404 tests pass unchanged.
- [ ] M6 Checks, review and report: full `composer check` and `composer check:reference`; Playwright at 1280px and 390px, light and dark, for M2 and M4; remove the fixed rows from `docs/exec-plans/tech-debt.md`; CHANGELOG (the unreleased 1.2.0 entry); reviewer rounds (at most 3); report; archive this plan.

## Validation
- Per milestone: `composer check:milestone -- <affected test paths>` passes; record the scope and the reason.
- Decision 1: in the browser, the search demo has no element with a border and rounded corners whose child also has one, except the search `Input` itself inside the top-bar strip.
- Decision 3: the Pest test above; the PDF of the Chinese title opened and looked at (rendered to an image or in the browser) shows the characters, not boxes.
- Decision 4: each check of decision 4 recorded in Progress with what was seen; the print page in print media has no frame around the sheet.
- Decision 5: Vitest: on a phone layout, ticking one card shows "Select this page", then "Select all N results"; the bulk action sends all results.
- Decision 6: no `app()->booted()` left in `routes/web.php`; RoutesTest passes; the 404 tests pass unchanged.
- Final implementation: full `composer check` and `composer check:reference` pass, repeated after any later code change. No package is added or upgraded.

## Progress
- 2026-11-14 02:25 MYT: The user reviewed batch 2 (the nested box in the search demo; the rest is fine) and answered grilling round 15 (Q92 to Q95) on the Q&A page, recorded above. Batch 2 is archived (final `composer check` and `composer check:reference` passed; review round 1 found ten P1, fixed; round 2 none). Plan written; waiting for the user to say start.
- 2026-11-14: The user said start. M1 done: DESIGN.md (Don't reworded, /style row says a demo has no backend), principle 10, print.md (the font), bulk-actions.md (phone). `composer check:milestone -- tests/Structure/DocsTest.php` passed (Pint, PHPStan, vp check, tsc, 6 tests): doc-only milestone, DocsTest is the test that reads them.

## Surprises & Discoveries
- `search-demo.tsx` draws the mock top bar as `flex h-14 items-center rounded-lg border px-4` inside `DemoFrame`, which is itself `rounded-lg border`: frame, box, input. The app's real header (`app-sidebar-header.tsx`) is a strip with `border-b`.
- The PDF view sets `font-family: "DejaVu Sans"`; DejaVu has no Chinese characters (tech-debt row of 2026-11-14).
- `BulkActionBar` offers "Select all N results" only once every row of the page is selected; on a phone there is no header checkbox to select the page.
- The fallback route is registered with `app()->booted()` in `routes/web.php` because RoutesTest's "a fixed path comes before a wildcard path" test reported it as hiding `storage/{path}`.

## Decision Log
- 2026-11-14: Work continues on the branch `frontend-design-v2-batch-1`; the user merges and releases only after every batch.
- 2026-11-14: The user said "开始" on this plan, which lists the protected files it changes. Approved and recorded: DESIGN.md, docs/golden-principles.md, docs/design/patterns/print.md, docs/design/patterns/bulk-actions.md (M1); tests/Structure/RoutesTest.php (M5, decision 6).

## Outcomes & Retrospective
Not started.
