# Ezdev Template: agent guide

This file is a map, not a manual. It stays under 100 lines.
Details live in the files it points to; read them when the task needs them.

## Commands

- `composer check`: every machine check (Pint, PHPStan with the layer rules, oxlint with the component boundaries, tsc, Vitest, Pest on MySQL). It must pass before you say "done". MySQL must be running. It takes a few minutes: run it in the foreground with a Bash timeout of 600000 ms.
- `composer check:milestone -- <test paths>`: all static checks and the affected tests, between milestones. `composer check:static`: just the static phase.
- `php artisan test --compact --filter=<Name>`: one test.
- `composer lint`: format PHP. `npm run check:fix`: format and lint the frontend.
- `composer check:reference`: the reference feature still passes every check. Run it after changing rules or tooling.
- `scripts/worktree-up <name>`: a git worktree with its own .env, database and port, for parallel work.

## How we work

Read docs/workflow.md before you answer a request, and follow its steps: questions, the plan (docs/PLANS.md), the user's "开始", the build, the review, the report. After "开始" you ask nothing.

Starting a new conversation? Read docs/exec-plans/active/ first and continue from Progress.

## Architecture (details: ARCHITECTURE.md)

- Layers, top to bottom: React, Http (Controller, FormRequest, Resource), Actions and Policies, Models, Enums. A layer may use the layers below it, never the ones above.
- Every business rule has exactly one owner (table in ARCHITECTURE.md). Every other place calls it.
- External services enter only through app/Integrations, called only from Actions.
- New features copy the structure of docs/reference/tasks/ and of the notifications feature in app/ (map in ARCHITECTURE.md). Never copy their domains.
- The starter kit's auth and settings code follows the same rules (app/Actions/Users, app/Policies/UserPolicy.php, app/Http/Fortify, app/Http/Controllers/Settings).

## Rules by folder: read before editing

| Editing                                                   | Read first                    |
| --------------------------------------------------------- | ----------------------------- |
| app/Actions/**                                            | docs/rules/actions.md         |
| app/Http/Controllers/**, app/Http/Fortify/**, routes/**   | docs/rules/controllers.md     |
| app/Http/Requests/**                                      | docs/rules/form-requests.md   |
| app/Models/**, app/Enums/**, app/Policies/**, database/** | docs/rules/models.md          |
| resources/js/**, app/Http/Resources/**                    | docs/rules/frontend.md        |
| resources/js/**, resources/css/**, DESIGN.md, docs/design/** | docs/rules/visual-design.md |
| app/**, resources/**, routes/** (any sentence a user reads) | docs/rules/translations.md |
| tests/**                                                  | docs/rules/tests.md           |

## Red lines

- Fix the code, not the test. Change or delete an existing test assertion only when a decision of the plan changes the requirement, and name that decision.
- Build only what the plan lists. What it does not say follows the template rules or keeps the current behaviour (docs/workflow.md, step 3).
- Never silence a check: no PHPStan or oxlint ignores, no wider allowIn, no skipped tests. The error says what to do instead.
- The template's own files are protected: the docs and the checks and tools, listed path by path in `PROTECTED_FILES` of scripts/hooks/finish-check.php, and the dependency sections of composer.json and package.json with their lock files. Change one only when the plan's "Approved changes", as the user approved it with "开始", names it. Anything else is reverted and named in the report.
- Do not create documentation files the user did not ask for, except plans and the docs this file lists.

## Docs map

- ARCHITECTURE.md: layers, dependency direction, where each rule goes
- docs/workflow.md: questions, plan, build, review, report
- docs/PLANS.md: how to write an ExecPlan
- docs/exec-plans/active/: work in progress. docs/exec-plans/completed/: finished plans
- docs/exec-plans/tech-debt.md: known debt, oldest first
- docs/golden-principles.md: every rule, listed under its review lane, with the check or reviewer that stops it
- docs/QUALITY.md: quality grade per domain
- docs/prompts/review.md, docs/prompts/cleanup.md, docs/prompts/upgrade.md: prompts for review, the weekly cleanup and upgrades
- docs/reference/tasks/: the reference feature
- DESIGN.md: the style of every screen, and which design file to read for which task
- docs/design/: foundations (colour, type, space, messages), components, and one file per page pattern

## Tools

- Boost MCP (`laravel-boost`): database-schema, search-docs, logs. Prefer it over guessing.
- Playwright MCP (`playwright` for `reviewer-opus`, `playwright-review` for `reviewer-haiku`): isolated headless browsers, never the user's Chrome; close them after checks. Screenshot handling: docs/rules/visual-design.md.
- Subagents: `explorer` (read-only lookups, small model), and `reviewer-opus` and `reviewer-haiku` (fresh context, read-only, two different models). They run in the foreground: the session waits for their answers.
- Finish check (scripts/hooks/finish-check.php): when you are about to stop, it sends back what is missing, one line each: a plan for changed code, a passing check, the review of a finished plan, the approval of a protected change. Do the step.

## Code Review Rules

The reviewers follow docs/prompts/review.md: one lane per run, findings only for what breaks a written rule, decision or walk item.
