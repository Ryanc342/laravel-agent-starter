# Frontend design v2, batch 1 feedback 2: column menus, centred states, quieter notifications

## Purpose
Apply the user's second review of batch 1 before it is merged: sort, filter and search from the column headers (for real, in the reference feature), empty and failed states drawn inside the table like the loading state, notifications marked as read by clicking them, and a pointing-hand cursor on everything clickable.

## Agreed decisions
From the user's second review of the batch 1 screens and grilling round 7 (Q53 to Q61), 2026-11-13:
1. Q53: the empty and the failed state sit inside the table's frame, under its header row, like the loading state: centred, a faint icon, one line, and the button when there is one. In the failed state the icon and the line are red.
2. Q54 and the user's note: a column header opens a menu. A text column offers sort (ascending, descending) and a search inside that column. A column with fixed options (a status) offers sort and a tick list where several options can be ticked, with "Select all" and "Unselect all". Every menu has "Clear". The search box above the table stays; the separate status filter above the table goes. This replaces the Q21 answer (filters in one row above the table).
3. Q55: a date or amount column offers sort and a from-to range.
4. Q56: on a phone, where the table is cards and has no header row, a "Sort & filter" button next to the search box opens the same choices in a sheet from the bottom.
5. Q57: sort, filters and search live only in the URL, like rows per page (Q43); changing any of them goes back to page 1. Following Q51, a value that is not allowed is ignored, as an unknown `per_page` is.
6. Q58: a sorted column shows an up or down arrow next to its name and a filtered column a funnel; both turn blue while in use.
7. Q59: the reference feature (`docs/reference/tasks`) gets real sort, filter and search, backend and frontend, with tests: search and sort on the title, a status tick list, sort and a from-to range on the due date.
8. Q60: clicking an unread notification marks it as read; the per-row "Mark as read" buttons go, and "Mark all as read" stays. A read notification is not clickable.
9. Q61: marking a notification as read, one or all, from the bell, the dashboard or the notifications page, leaves the user on the page they were on. This changes the requirement behind two existing assertions in `tests/Feature/Notifications/NotificationControllerTest.php` ("a user can mark their own notification as read" and "a user can mark all of their notifications as read" expect a redirect to the notifications page); they change to expect a redirect back.
10. The user's note: everything clickable shows the pointing-hand cursor; a disabled control keeps the normal arrow.

## Milestones
- [x] M1 Spec and rules: `docs/design/patterns/list.md` (column menus, top search, ranges, mobile sheet, URL, indicators), `states.md` (centred inside the table), `feed.md` (click to mark as read, no row buttons), `docs/design/components.md` (new shared blocks, cursor), DESIGN.md where it names these, principle 10 in `docs/golden-principles.md`, `docs/rules/frontend.md` (sort, filter and search come from the list's FormRequest; filter options come from the backend as `{ value, label }`). + DocsTest.
- [ ] M2 Cursor and states: the pointing-hand cursor for every enabled clickable element in `resources/css/app.css` (disabled ones keep the arrow); `EmptyState` and `ErrorState` centred with an icon; `ResponsiveTable` and the feed list show empty, failed and loading inside their frame. + Vitest.
- [ ] M3 Column menus (frontend, `components/shared`): a column header menu with sort, column search, tick list with Select all and Unselect all, from-to range and Clear; the arrow and funnel indicators; the "Sort & filter" sheet on a phone; every change goes to the URL through Inertia and resets the page to 1, keeping `per_page`. No new package (date ranges use the native date input). + Vitest.
- [ ] M4 Reference feature backend: `IndexTaskRequest` reads `search`, `sort`, `direction`, `status[]`, `due_from` and `due_to` from allowed values only (anything else ignored); `Task` scopes do the search, filters and order; the status options come from `TaskStatus`; page links keep the query string. + feature tests for each filter, sort direction, combinations, ignored values and kept links; `composer check:reference`.
- [ ] M5 Reference feature frontend and style page: the task list uses the column menus, the top search and the phone sheet; the style page's list demo shows an amount column with a range, with example data passed through props. + `composer check:reference`.
- [ ] M6 Notifications: an unread row is a button that marks it as read; the row buttons go; `NotificationReadController` and `AllNotificationsReadController` redirect back; the two assertions named in decision 9 change. + feature tests, Vitest for the row.
- [ ] M7 Checks, review and report: full `composer check` and `composer check:reference`; Playwright at 1280px and 390px, light and dark, on every changed page, including a column menu, the phone sheet and the cursor; CHANGELOG entry; reviewer rounds (at most 3); report; archive this plan.

## Validation
- Per milestone: `composer check:milestone -- <affected test paths>` passes; record the scope and the reason.
- M1: `tests/Structure/DocsTest.php`.
- M2: Vitest shows the empty and failed states centred inside the table frame with the header row kept; a check in the browser shows the hand cursor on a button, a link, a tab of the column menu and a clickable notification, and the arrow on a disabled button.
- M3: Vitest shows sort, column search, Select all and Unselect all, Clear, and that a change requests page 1 and keeps `per_page`.
- M4: feature tests (through `composer check:reference`): search finds by title; each sort direction orders as expected; several ticked statuses filter together; the due-date range is inclusive at both ends; an unknown `sort`, `direction` or status is ignored; the next-page link keeps every parameter.
- M6: feature tests: marking one or all as read redirects back (from the dashboard and from the notifications page); the two changed assertions are named in Progress with the reason (decision 9).
- UI: every changed page opened with the isolated headless Playwright MCP at 1280px and 390px, light and dark.
- Final implementation: full `composer check` and `composer check:reference` pass, repeated after any later code change. No package is added or upgraded.

## Progress
- 2026-11-13 16:15 MYT: The user reviewed the screens again; grilling round 7 (Q53 to Q61) is answered on the user's Q&A page and recorded above, with two notes (Select all / Unselect all in a tick list; the pointing-hand cursor). Plan written. Waiting for the user's start and approval of the protected files and the changed assertions listed in the Decision Log.
- 2026-11-13: The user said "开始" (start). Taken as the start, as confirmation of the branch, and as approval of the protected files and the two changed assertions named in the Decision Log. M1 begins.
- 2026-11-13: M1 done (docs only, so no test written first; 0 failing before). Scope: the spec files in the Decision Log, tests/Structure/DocsTest.php. `composer check:milestone -- tests/Structure/DocsTest.php`: passed (static checks, 4 tests). Milestone/affected-test pass, not a full-suite pass.

## Surprises & Discoveries
- `EmptyState` and `ErrorState` lay out their line and button to the left (`items-start`); only the loading state was drawn inside a table frame.
- Search and the status filter existed only in the style page's demo; the reference task list had no search, sort or filter in its backend.
- Both read controllers end with `to_route('notifications.index')`, which is why marking as read from the bell or the dashboard left the page.
- Tailwind 4 gives buttons the normal arrow; `resources/css/app.css` sets no cursor today.

## Decision Log
- 2026-11-13: Work continues on the branch `frontend-design-v2-batch-1`, not merged yet, so batch 1 and both feedback rounds are merged together. The user confirms this at start.
- 2026-11-13: Protected files this plan changes, awaiting the user's approval at start: DESIGN.md, docs/design/, docs/golden-principles.md, docs/rules/frontend.md and docs/reference/tasks/. Existing assertions this plan changes, because the user changed the requirement (decision 9): the two tests named there. Ask before touching any other protected file or assertion.
- 2026-11-13: The user said "开始" and so approved the protected files and the two assertions above: DESIGN.md, docs/design/ (components.md, patterns/list.md, patterns/states.md, patterns/feed.md), docs/golden-principles.md, docs/rules/frontend.md, docs/reference/tasks/ (README.md and its app, resources, routes and tests files), and tests/Feature/Notifications/NotificationControllerTest.php (the two assertions in decision 9).
- 2026-11-13: Small choices not covered by Q53 to Q61, made while writing the spec (the user can change them): the top search box searches after a short pause in typing or at Enter, with no search button; the "Clear" in a sheet on a phone clears every sort, filter and search choice; the new shared blocks are named `ColumnMenu` and `SortFilterSheet`.

## Outcomes & Retrospective
To be filled in at the end.
