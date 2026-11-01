# Ezdev v2: agent guide

This file is a map, not a manual. It stays under 100 lines (tests/Structure checks it).
Details live in the files it points to; read them when the task needs them.

## Commands

- `composer check`: every check (Pint, PHPStan, Pest incl. Arch and Structure, tsc, lint, dependency-cruiser). It must pass before you say "done".
- `php artisan test --compact --filter=<Name>`: one test.
- `composer lint`: format PHP. `npm run check:fix`: format and lint the frontend.
- `composer check:reference`: the reference feature still passes every check. Run it after changing rules or tooling.
- `scripts/worktree-up <name>`: a git worktree with its own .env, database and port, for parallel work.

## How we work (details: docs/workflow.md)

1. Grill. Every request starts with questions in the user's language, in the format in docs/workflow.md. Look facts up yourself; put every decision to the user.
2. Plan. When no decision is open, write docs/exec-plans/active/<feature>.md (format: docs/PLANS.md). Change no code until the user says "start". A one-line change with no open decision may skip the plan; say so.
3. Build, one milestone at a time: Pest tests first, then code from the bottom layer up, then `composer check`, then commit, then update Progress in the plan.
4. Review. Ask the `reviewer` subagent to compare the diff with the plan (docs/prompts/review.md). Fix P0 and P1 findings. At most 3 rounds.
5. Report where each agreed decision lives, the check output, and screenshots for UI changes. Move the plan to docs/exec-plans/completed/.

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
| resources/js/**, resources/css/** when DESIGN.md exists   | docs/rules/visual-design.md   |
| tests/**                                                  | docs/rules/tests.md           |

## Red lines

- Fix the code, not the test. Change an assertion only when the user changed the requirement, and say why.
- Build only what the plan lists. Anything else is a new question for the user.
- Never silence a check: no PHPStan ignores, no wider allowIn, no skipped tests. The error says what to do instead.
- Do not change AGENTS.md, ARCHITECTURE.md, docs/rules/, docs/golden-principles.md or check configs without the user's approval.
- Do not add or upgrade dependencies without the user's approval.
- Do not create documentation files the user did not ask for, except plans and the docs this file lists.

## When stuck

If you get stuck, or the user corrects the same thing twice, say what was missing (a doc line, a rule, a check, a tool) and propose the smallest addition. The user decides.

## Docs map

- ARCHITECTURE.md: layers, dependency direction, where each rule goes, which check enforces it
- docs/workflow.md: grilling format, plan, build, verify, review, report
- docs/PLANS.md: how to write an ExecPlan
- docs/exec-plans/active/: work in progress. docs/exec-plans/completed/: finished plans
- docs/exec-plans/tech-debt.md: known debt, oldest first
- docs/golden-principles.md: what review and the weekly cleanup check
- docs/QUALITY.md: quality grade per domain
- docs/prompts/review.md, docs/prompts/cleanup.md, docs/prompts/upgrade.md: prompts for review, the weekly cleanup and upgrades
- docs/reference/tasks/: the reference feature
- DESIGN.md (optional, project root): visual style only

## Tools

- Boost MCP (`laravel-boost`): database-schema, search-docs, logs. Prefer it over guessing.
- Playwright MCP (`playwright`): open pages in its own browser. Never use the user's Chrome.
- Subagents: `explorer` (read-only lookups, small model) and `reviewer` (fresh context, read-only).

## Code Review Rules

Report P0 and P1 only:

- a business rule written in a second place instead of calling its owner
- a model write outside an Action, or a permission check outside the controller
- React deciding prices, totals, statuses, deadlines, permissions or eligibility
- behaviour that is not in the plan, or an agreed decision that is missing
- an existing test assertion edited or deleted
- an Action or endpoint without a feature test

Skip style and naming.
