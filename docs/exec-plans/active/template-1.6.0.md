# Template 1.6.0: a lighter template, review rules with levels, samples apart from live pages

## Purpose
The template is built for Ryan alone and projects rarely add rules, so it should be easy to maintain and still general. This release removes most of the machinery that polices the AI, gives every review rule a level and one plain job, separates the design samples from the pages a project uses, and drops two sidebar links. Project quality must not drop.

## Agreed decisions
Agreed with Ryan in the planning chat on 2026-11-20. The group names are for reading only.

Version and branch
1. 1.6.0 is one release, built on the branch `template-simplification` on top of the parked 1.5.0 work (its plan, M1 to M8k, is in docs/exec-plans/completed/template-simplification.md). 1.5.0 is never trialled or released. `main` moves only when Ryan says "发布". One trial after every milestone is done.
2. New projects keep being made with `laravel new`, so the Laravel installer path and its check stay.

Finish check
3. The Stop hook checks one thing: code changed in this conversation and no `composer check` or `composer check:milestone` passed on the current code. Then it sends the AI back once, never twice in a row (`stop_hook_active`), as now.
4. The Stop hook also prints one line to Ryan, without blocking, when this conversation changed a check file: phpstan.neon, vite.config.ts, tsconfig.json, phpunit.xml, pint.json, lint/ or tests/Structure/ ("This conversation changed a check: <files>").
5. The SessionStart hook, the UserPromptSubmit hook (the "开始" snapshot) and the SubagentStop hook (the review ledger) are removed.
6. Proof that a check passed: when `composer check` or `composer check:milestone` passes, it records a fingerprint of the current code; the Stop hook compares only that. The check lock, the end-of-suite proof (tests/Structure/RunFinishedTest.php) and the checking/checked attempt states are removed.
7. The protected-file list (`PROTECTED_FILES`, 163 files) and everything built on it are removed.
8. Plans have no "Approved changes" section and there is no approval snapshot: Ryan's "开始" in the chat is the approval. A dependency to add and an existing test assertion to change are written as ordinary agreed decisions.
9. The AGENTS.md red line about protected files becomes one sentence: never silence a check (no ignore comments, no skipped tests, no lowered level or wider exception). "Fix the code, not the test" stays.

Reviews
10. Three review lanes instead of six: backend (business, input, tests), frontend (frontend) and screens (look, behaviour). A round is at most six runs.
11. The review ledger goes. Each round's result is one Progress line in the plan.
12. Screens: one app copy, and only `reviewer-opus` looks at pages (the walk and the look). `reviewer-haiku` reviews code only; its browser server (`playwright-review`, WebKit) and its browser tools go.

Template-only files
13. Files that serve only the template move into one folder, excluded once in .gitattributes (`export-ignore`, so `laravel new` leaves them out) and once in copier.yml (`_exclude`, so syncs leave them out). They are: scripts/check-installer, tests/Unit/Lint/SkeletonChecksTest.php, CHANGELOG.md, the template's own plans in docs/exec-plans/completed/, the Stop hook's remaining tests, and the copy check of decision 31. The template's CI still runs them.
14. The hook tests of the removed machinery (most of tests/Unit/Hooks) go with it. The structure tests that keep a check from being silenced or turned down stay in projects.

Plans, reports, docs
15. docs/QUALITY.md's grade table goes, with every step that updates it.
16. The cleanup is run when Ryan wants it: docs/prompts/cleanup.md stays; nothing says "weekly" any more.
17. The sentences in rule files about the old machinery change with it: checks.md ("Changing a check is a protected change"; "every check proved on a broken fixture" now holds in the template only), tests.md ("lint/, tests/Structure/ and tests/Support/ ... are protected"), visual-design.md ("the reviewers check [review:look] and [review:behaviour] ... on screenshots": now reviewer-opus).
18. The docs follow every decision above: AGENTS.md, docs/workflow.md, docs/golden-principles.md, docs/PLANS.md, docs/prompts/review.md, docs/prompts/cleanup.md, docs/prompts/upgrade.md, README.md and README.zh-CN.md.

Review rules: format and levels
19. Every review rule says one thing and is written as: the rule in one sentence, its level, and why in one sentence; where the edge is easy to get wrong, one more sentence starting "Not a break:". Rules carry no right or wrong examples.
20. Levels, defined once in docs/golden-principles.md. Level 1, baseline: breaking it causes an error now (wrong behaviour, wrong data, a permission hole, wrong money, a broken page). Level 2, anti-spaghetti: no error now, but the same thing is written twice or in the wrong place, so a later change misses one. Level 3, consistency: no error and no harder to change, only different from the rest.
21. Every finding is fixed, at every level: all three block delivery. P0 and P1 go: a finding carries its rule's level, the result line counts findings per level, and the report lists them by level.
22. A rule's tag holds its lane (decision 10) and its level.
23. The code examples in rule files (correct and wrong code blocks) are removed; each points to the matching file of the reference feature (docs/reference/tasks/) instead.
24. A rule that holds several things is split: what a shared component already does is not a rule of its own (one rule says to use the component); a part that repeats another rule is merged into it; only the independent parts become rules of their own.
25. Rules that change in meaning (the review-rule audit of 2026-11-20; rules named by file and first words):
    - actions.md "`handle()` takes no `bool` parameter": only a bool that switches between two behaviours is banned; a yes/no the user entered (a validated field such as "notify the customer") is data and may be passed.
    - models.md "Inside a model, a column that a scope filters on is not compared again": for a fixed value (status, type) the scope and the method both ask the same enum method, with no query; only conditions such as dates make the method ask the scope with a query.
    - visual-design.md "One rule for where a form lives": the rule stays; `ResponsiveDialog` gets a `wide` size, next to `narrow`, for long forms such as an invoice with many lines, with its Vitest test.
    - actions.md "`lockForUpdate()`, `sharedLock()` and `lock()` are called only in Actions": two rules: a lock only inside the transaction of the operation that writes; when two Actions lock the same row, the lock is written once. The lock-test requirement moves to tests.md.
    - app.md "No copied blocks of code": a copy is three or more lines of logic that do the same job; display markup that only looks alike is not a copy.
    - app.md "Only Actions write": one sentence (whatever changes the database, sends mail or sends a notification is in an Action), with no open-ended list and no "and the rest".
    - controllers.md "A controller method authorizes, calls one Action": a read may run several scoped queries; a write calls one Action.
    - form-requests.md: "unique among the user's own records" is checked in the FormRequest (a `unique` rule scoped to the owner); the rule says so, and the untagged sentence that bans owner conditions on `unique` goes.
    - frontend.md "React does not compare statuses, roles or types": only the business statuses, roles and types the backend sends; the page's own state (the selected tab, the view mode, an open dialog) is not covered. "filtering a list the user is looking at" goes from the paragraph above it (filters live in the URL).
    - frontend.md "No hard-coded URLs": the app's own routes only; external links (help pages, mailto) are not covered.
    - frontend.md "No UI library besides components/ui": bans a component kit (a set of ready-made buttons, inputs, dialogs); a single-purpose library (a rich-text editor, drag and drop) is named as an agreed decision of the plan that adds it.
    - models.md "Factories set values through enum cases and states": one sentence says what restating a condition is, checked against docs/reference/tasks/database/factories/TaskFactory.php so the reference does not break it.
    - tests.md "Every endpoint is tested for guests, other users (403), invalid input and success": only the cases the endpoint has (no input, no invalid-input test; no owned record, no other-user test).
    - visual-design.md "A list of records is a `ResponsiveTable`": split by decision 24; its notification-list part and "A feed row has no card and no grey background" become one rule.
    - visual-design.md "Every row of action buttons is the shared `Actions` block": "a component that is not `Actions` places its own buttons" goes, or names the components it means.
    - visual-design.md "The first screen after login is the dashboard" and "Explaining text appears only for an unusual state": one rule, split by decision 24, saying plainly whether a field may have a line under it (Open questions, Q1).
    - visual-design.md "A change to a screen changes only what the plan names": what the plan does not name is not touched; "removing or simplifying is preferred" applies only inside what the plan changes.
    - app.md "Two conditions that mean the same thing": becomes an untagged sentence above the rules it sums up.
    - visual-design.md "The display sizes belong to the two blocks": merged into "The page title is `<Heading>`".
    - visual-design.md "Every sentence passed to `t()`": checks only the change: a new sentence has its Chinese, a removed sentence has its translation removed.
26. Six requirements written only as untagged text become tagged level-1 rules: actions.md "Business conditions"; actions.md "External services"; controllers.md step 1 (list, create and bulk endpoints ask the Policy too); controllers.md "Reads" (relations and counts loaded in the same query, large tables read in pieces); form-requests.md (uploaded files: type and size limits, stored under a name the app makes); models.md (a migration that ran elsewhere is never edited).
27. Every other review rule is rewritten in the new format with the same meaning. Levels, by file, in the order the file lists its review rules (machine rules not counted): actions.md 2, 3, 2, 3, 1, 2, 1, 1, 1; app.md 2, 2, 2, 2, 2, 2, 1, 2, 2, 2, 2, 2, 3; controllers.md 2, 1, 2, 1, 3, 2, 2; fixes.md 1, 1, 1; form-requests.md 2, 1, 2; frontend.md 1, 2, 1, 1, 2, 2, 1, 1, 2, 1, 2, 2, 2, 2, 3, 3; models.md 2, 2, 3, 2, 2, 2; tests.md 2, 2, 1, 1, 2, 3, 2, 3; visual-design.md, Look 2, 2, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 2, 3, 3, 3, 3, 1, 2, 3, 3; visual-design.md, Behaviour and Text 3, 1, 3, 3, 3, 3, 3, 3, 3, 1, 1, 1, 3, 2, 3, 3, 1, 1, 3, 3, 3. A rule split by decision 24 keeps its level in each part unless a part is clearly level 1.
28. The machine rules (`[PHPStan]`, `[oxlint]`, `[Structure]`) keep their meaning.

Samples apart from live pages
29. New folder docs/reference/pages/ with copies of the pages the design files tell the AI to copy: login, register, dashboard, error page, notifications page, welcome, the three settings pages, and the notification row component.
30. The seven pattern files that point at live pages (auth, dashboard, error-page, feed, states, landing, settings) point at the copies. The frames and shared parts they name (the auth frame, the public frame, the settings frame, FeedRow and the like) say "use" instead of "copy". The live pages become ordinary pages a project may change or delete; the check that every named file exists then reads the copies.
31. A check that each copy equals the template's own page runs only in the template (decision 13). The /style demos do not change.

Sidebar
32. The sidebar's bottom "Repository" and "Documentation" links go: `footerNavItems` in resources/js/components/app-sidebar.tsx, resources/js/components/nav-footer.tsx (used only there), its entry in the allowed list of tests/Structure/PlacementChecks.php, and the two sentences in lang/zh_CN.json.

Release
33. CHANGELOG.md has one 1.6.0 entry: the unreleased 1.5.0 entry plus this plan. Both READMEs name 1.6.0.
34. Existing test assertions that change: tests/Unit/Hooks (decisions 3 to 8, 11, 12), tests/Structure where a check reads a removed file, the protected list, a rule's text or tag, a pattern's sample path or the sidebar (decisions 4, 7, 13, 17, 19 to 27, 30, 32), and tests/Unit/Lint/SkeletonChecksTest.php (it moves, decision 13). No dependency is added or upgraded.
35. The pages look like the accepted previews, in storage/app/private/design-previews/template-1.6.0/: sidebar-1280x800.png and sidebar-phone-390x844.png (the dashboard and the phone menu, with only the user at the bottom of the sidebar), wide-dialog-1280x800.png and wide-dialog-phone-390x844.png (an invoice with six lines in the wide dialog; on a phone the same full-width sheet as every dialog). The invoice and its line rows are example data for the size only: this plan builds no line-item component.
36. `ResponsiveDialog`'s `wide` size is 48rem (768px) on desktop (`sm:max-w-3xl`), against the default 32rem and `narrow`'s 28rem; on a phone it is the full-width sheet like every size. `narrow` and `wide` are one prop with three values or two exclusive booleans, whichever keeps the existing callers unchanged; the Vitest test covers the wide size on desktop and on a phone. The /style demos stay as they are (decision 31); docs/design/components.md and docs/design/patterns/form.md say when to use `wide` (a long form with a table of lines).

Answers to the open questions (Ryan, 2026-11-20)
37. Q1, explaining text (decision 25): a form field may have one line under it only for a step the user must take (the password requirements), nowhere else. The visual-design.md rule says so plainly.
38. Q2: 1.6.0 adds no line under the password fields; the rule allows it, and "show the password requirements under the password fields (register, reset password, security)" goes into docs/exec-plans/tech-debt.md for a later release. No page changes beyond the sidebar.
39. Q3, the template-only folder (decision 13): `template/` at the root. Its tests run from the template's CI with their own PHPUnit configuration, so a project's phpunit.xml names no missing folder.

## Open questions
None left: Q1 to Q3 are answered in decisions 37 to 39.

## Approved changes
The finish check of 1.5.0 still runs until M1 replaces it, and it reads this section from the snapshot that "开始" takes:
- Protected files: `.claude/`, `docs/`, `scripts/`, `tests/`, `lint/`, `.github/`, AGENTS.md, ARCHITECTURE.md, DESIGN.md, phpstan.neon, vite.config.ts, tsconfig.json, pint.json, phpunit.xml, .gitignore, copier.yml (decisions 3 to 34)
- Dependencies: none
- Existing test assertions that change: decision 34
This section goes from PLANS.md in M4 (decision 8); this plan keeps it as history.

## Milestones
- [x] M1 Finish check (decisions 3 to 9): tests first in tests/Unit/Hooks; the one Stop check and the check-file line; the other hooks, the check lock and proof, the protected list and the snapshot go.
- [x] M2 Reviews (decisions 10 to 12): three lanes, no ledger, one screen copy for reviewer-opus; docs/prompts/review.md and the agent files.
- [x] M3 Template-only folder (decisions 13 and 14): move the files, the two excludes, CI; check with `git archive` that a release leaves them out.
- [x] M4 Plans, reports and docs (decisions 15 to 18).
- [x] M5 Rule format and levels (decisions 19 to 24): golden-principles, tags, the result line, no code examples.
- [x] M6 The changed rules and the six new ones (decisions 25 and 26), and `ResponsiveDialog`'s `wide` size.
- [x] M7 Every other review rule in the new format (decisions 27 and 28).
- [x] M8 Sample copies (decisions 29 to 31).
- [x] M9 Sidebar links (decision 32).
- [ ] M10 Final: full `composer check` and `composer check:reference`, the final review of every touched lane, CHANGELOG.md and the READMEs (decision 33), one trial from a small new request in a scratch worktree, then stop for "发布".

## Validation
- Per milestone: `composer check:milestone -- <affected test paths>`
- Final: full `composer check` and `composer check:reference`
- tests/Unit/Hooks: a stop with changed code and no pass on it is sent back once, and the next stop goes through with one line for Ryan; a pass on the current code lets the AI stop; a changed check file gives Ryan one line and does not block.
- A scratch copy of the template with registration turned off and the register page deleted passes every check (decision 30).
- The copy check fails when a copy differs from its page (decision 31).
- `git archive HEAD` holds none of the template-only files (decision 13).
- W1 user (test@example.com): sign in, the dashboard opens, the sidebar has no Repository or Documentation link, open Settings and come back.

## Progress
- 2026-11-20 Plan written in the planning chat; the 1.5.0 plan moved to completed/ as parked, commit 470ae10a.
- 2026-11-20 Previews made (decision 35), the wide size settled (decision 36), Q2 and Q3 added from the code.
- 2026-11-20 Q1 to Q3 answered by Ryan (decisions 37 to 39).
- 2026-11-20 M1 Finish check: one Stop check, the check-file line, a fingerprint pass; milestone check passed, commit 668db900.
- 2026-11-20 M2 Reviews: lanes backend, frontend and screens (tags renamed), no ledger, one screen copy for reviewer-opus, reviewer-haiku code only; milestone check passed, commit c6a7494f.
- 2026-11-20 M3 Template-only folder: template/ holds CHANGELOG.md, the template's plans (template/plans/), check-installer, the hook and fixture tests with their own phpunit.xml (composer check and check:milestone run them when template/ exists); `git archive HEAD` holds no template/ file; milestone check passed, commit 1faed355.
- 2026-11-20 M4 Plans, reports and docs: docs/QUALITY.md removed, PLANS.md without Approved changes, cleanup on demand, rule-file sentences, AGENTS.md, workflow, prompts, CONTRIBUTING and both READMEs follow decisions 3 to 17; milestone check passed, commit 9c47e334.
- 2026-11-20 M5 Rule format and levels: golden-principles defines the rule form and the three levels, tags are `[review:<lane>:<level>]` with the levels of decision 27, findings and the result line count L1/L2/L3, the report lists them by level, the eight code examples point to reference files; milestone check passed, commit b5a7619d.
- 2026-11-20 M6 Changed and new rules: decisions 25, 26 and 37 in actions, app, controllers, form-requests, frontend, models, tests and visual-design; `ResponsiveDialog` `wide` (48rem, `data-size`) with 3 Vitest tests that failed before the code, components.md and form.md; milestone check passed, commit 61e7f1a4.
- 2026-11-20 M7 Every other review rule: fixes.md without examples; all 123 review rules carry `[review:<lane>:<level>]` and a Why, the [PHPStan], [oxlint] and [Structure] lines are as they were before M5; milestone check passed, commit 29a649c3.
- 2026-11-20 M8 Sample copies: ten copies in docs/reference/pages/ (paths under resources/js/ without the leading pages/), the seven pattern files and frontend.md's breadcrumb pointer name them and say "use" for the frames and shared parts, template/tests/Lint/SampleCopiesTest.php checks each copy and proves the check fails on a differing, missing or extra copy; milestone check passed, commit 4116c379.
- 2026-11-20 M9 Sidebar links: footerNavItems, nav-footer.tsx, its PlacementChecks entry and the two zh_CN sentences removed; no test was added, as the template tests no layout-only component (the walk W1 checks it); milestone check passed, commit 5335bd25.
- 2026-11-20 M10 Full `composer check` and `composer check:reference` passed; CHANGELOG 1.6.0, READMEs name 1.6.0, the password-line debt added; decision-30 trial: a copy without registration and its register page passes Pint, PHPStan, oxlint, tsc, Vitest and the project's Pest suite (the template-only copy check flags the changed pages, as it should); commit 60350bf0.
- 2026-11-20 Review round 1, lanes backend and frontend: 5 findings, all level 1 (frontend, both reviewers: `narrow` and `wide` could both be set; backend opus: a failing `git log` read as no change; backend haiku: the tests of screen-round, playwright-mcp, the review round and the milestone check had moved to template/ though projects keep those scripts, and the project half of the check-call test was lost). Fixed: an exclusive width type, `mustGit` for every git answer the check relies on with a test that failed first, the four tests back in tests/Unit/Hooks, the check-call test back in tests/Structure, checks.md's paths; commit d9a76ad8.
- 2026-11-20 Review round 2: frontend done (both reviewers 0). Backend: opus 0; haiku L1=2 L3=1: the hook tests wrote an empty script when it was missing, and the copy check hid a missing page behind "differs" (fixed, the copy check's test covers it); the L3 "helper functions in a test file" is not a break: tests.md's folder rule is about files, and its Style section names helper functions in test files. Also fixed from both reviewers' older-code notes: the plan's base commit worked out once (`planBase`), a failing git there and a missing template commit now fail the round, with a test that failed first; commit ca04fe3c.
- 2026-11-20 Review round 3, backend: 3 findings, all level 1 (opus: a failed `proc_close` of the new-file diff counted as success; haiku: any git refusal at the start read as "no repository", and the state folder fell back to a guessed path). Fixed: `gitRun` tells "not a repository" and "no commit yet" from a refusal, which reaches Ryan with git's message (test failed first; a test for "before the first commit" added), the state folder through `mustGit`, the diff exit must be 1. Correction to round 2: its test covers the missing template commit; the planBase git failure is covered by `mustGit`, not by its own test; commit b689ff2a.

## Surprises & Discoveries
- 2026-11-20 M6 rewrote each of the eight rule files it touched as a whole, so every other rule of those files is already in the new format with its decision-27 level; M7 is left with fixes.md and the check of decisions 27 and 28. Splits by decision 24 (levels kept unless a part is clearly level 1): controllers (Policy for list/create/bulk, app/Http folders, route binding, fixed before wildcard path: level 1), actions (lock in the writing transaction / lock written once), tests (lock test), visual-design (feed rule merged with the notification list; URL state; submit disabled while sending, readable text, import and export: level 1; report range in the URL).

## Decision Log

## Outcomes & Retrospective
Rule gaps:
