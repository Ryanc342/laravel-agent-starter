# Golden principles

The principles of this template, the review lanes, and how a rule is written and found. The rules themselves are in .claude/rules/, each written once, in the file of the folders it is about; Claude Code loads a file when the AI reads a file its `paths:` match. Process rules are in AGENTS.md ("Red lines") and docs/workflow.md. Details and examples are in ARCHITECTURE.md.

## How a rule is written

A rule is a list item in a .claude/rules/ file that starts with its tag, which says who checks it:

- `[PHPStan]` the PHP linter (the layer rules in phpstan.neon) and `[oxlint]` the frontend linter (the `lint` block in vite.config.ts and Ezdev's rules in lint/oxlint/ezdev.js). A linter reads the code without running it. Both run in `composer check`; the change cannot be committed until they pass.
- `[Structure]` tests/Structure: checks a linter does not do. FoldersTest.php and CodeTest.php read where files are and what they hold, ConfigTest.php reads the configs, DesignDocsTest.php reads the design spec. tests/Unit/Lint/SkeletonChecksTest.php breaks every machine rule once in a fixture and proves that its check still fails. Also in `composer check`.
- `[review:<lane>]` the two reviewers (`reviewer-opus`, `reviewer-haiku`) read the change for it in that lane, one lane per run, and the weekly cleanup reads the repository for it. No machine checks it.

Text in a rules file without a tag explains how to follow the rules; it is not a rule of its own. A reviewer finds the rules of its lane by searching .claude/rules/ for its tag (`[review:business]`), and lists them in the order it finds them, file by file in name order (docs/prompts/review.md, step 4).

Machines check the layers, the folders of app/, where pages and components go, that code lives where the checks read it, and that no check is silenced or turned down. Everything else needs someone who reads the code or looks at the screen, so it belongs to the reviewers.

## The principles

1. Every business rule is written once, by its owner, and everything else calls it.
2. Layers only depend downwards: React, Http, Actions and Policies, Models, Enums.
3. Every class does only its own job.
4. The frontend never does the backend's job: React never calculates prices, totals, statuses, deadlines, permissions or eligibility; the Resource sends the results (`is_*`, `can.*`).
5. Input is checked once, at the edge.
6. Keep it simple.
7. Files go where the conventions say.
8. Every feature is tested.
9. Work follows the steps of docs/workflow.md.
10. Screens follow DESIGN.md and docs/design.

## Review lanes

One reviewer run covers one lane. A change needs rounds only for the lanes it touches: it adds, changes or deletes a file in one of the lane's places.

| Lane                        | Key         | Rules                                   | Touched by files in                                                  |
| --------------------------- | ----------- | --------------------------------------- | -------------------------------------------------------------------- |
| Business and class jobs     | `business`  | `[review:business]`, principles 1 and 3 | app/, database/, routes/                                             |
| Input and placement         | `input`     | `[review:input]`, principles 5, 6 and 7 | app/, database/, routes/, resources/js/, composer.json, package.json |
| Frontend logic              | `frontend`  | `[review:frontend]`, principle 4        | resources/js/, app/Http/Resources/, app/Http/Requests/               |
| Tests                       | `tests`     | `[review:tests]`, principle 8           | tests/, app/, routes/, resources/js/hooks/, resources/js/lib/        |
| Screens: look               | `look`      | `[review:look]`, principle 10           | resources/js/, resources/css/, DESIGN.md, docs/design/               |
| Screens: behaviour and text | `behaviour` | `[review:behaviour]`, principle 10      | resources/js/, resources/css/, resources/views/, lang/, app/         |

The final review reviews every lane the plan touches, both reviewers at once, and the screens last, when the other lanes are done, so a backend change that breaks a page is still caught at the end (docs/workflow.md, step 4).

A file of the reference feature (docs/reference/tasks/) other than Markdown touches the lanes of its path inside the feature: docs/reference/tasks/resources/js/ as resources/js/, docs/reference/tasks/app/ as app/.

Principles 2 and 9 have no review lane: the machines and the finish check (AGENTS.md, "Tools") check them.
