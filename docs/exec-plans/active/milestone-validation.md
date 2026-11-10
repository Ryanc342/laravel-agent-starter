# Milestone validation and final full acceptance

## Purpose
Keep milestone feedback fast as a project grows, while requiring complete checks on the final plan implementation before delivery. Publish the updated template to GitHub and Packagist.

## Agreed decisions
1. Each milestone runs tests for the affected behavior and its callers, including unchanged dependent tests, plus full formatting, static analysis and type checks. Changed pages still receive desktop and phone UI inspection.
2. The completed plan must pass the unchanged full scope of `composer check`. Review fixes require affected tests and a full check on the final code. Shared test/database infrastructure or an impact scope that cannot be bounded needs an earlier full check.
3. Milestone results must be recorded as partial validation, never as a full-suite pass. A separate check command and fingerprint may permit an intermediate stop only for an incomplete active plan; final acceptance keeps the existing full-suite fingerprint and completion proof.
4. Synchronize the agent guide, workflow, plan template, test guidance, golden principles, public guides and related maintenance prompts. Preserve business tests, dependencies, MySQL isolation and CI full validation.
5. The user approved implementation, committing, pushing and Packagist publication with “同意，你来执行修改吧，然后帮我提交推送，记得packagist”. This is authorization to start, not a pending proposal.

## Milestones
- [x] M1 Implement milestone validation with regression tests, synchronized instructions, full source/reference verification and independent review.
- [ ] M2 Commit and push the reviewed change, publish the next public minor release, and verify GitHub CI and Packagist source references.

## Validation
- First run the new hook/runner regression tests against the existing behavior and observe failures.
- `composer check:milestone -- tests/Unit/Hooks tests/Structure` runs all static checks and only the selected tests; PHP and frontend selections, invalid/empty scopes, command failures and edits during validation receive regression coverage.
- An incomplete active plan accepts a fresh milestone result; a complete, archived, missing or invalid plan cannot use it instead of `composer check`. Old or failed milestone runs never count.
- `CI=true composer check` and `CI=true composer check:reference` pass, including the final code after review changes. No UI changed, so UI inspection is not applicable.
- Push only the intended branch and new release tag. Verify the GitHub workflow and public Packagist stable/dev source references match the release commit.

## Progress
- 2026-11-10 Inspected current rules, hook behavior and publication setup. Working tree is clean; public latest is v1.0.1 and automatic Packagist sync is enabled. Local v2.x tags are legacy history and will not be pushed.
- 2026-11-10 M1 regression-first evidence: all 16 new runner/hook scenarios failed against the old behavior. After implementation those 16 passed (47 assertions); added explicit archived-record and frontend-only regression cases before integrated validation. ConfigTest's script composition assertion is updated for the approved shared static phase, retaining every full-suite step.
- 2026-11-10 M1 integration: `composer check:milestone -- tests/Unit/Hooks tests/Structure tests/js/lib/format.test.ts` passed all static checks, 87 PHP tests / 210 assertions and the three selected frontend tests. `CI=true composer check` passed 263 PHP tests / 1003 assertions and all six frontend tests; `CI=true composer check:reference` passed 311 / 1160 and six frontend tests. UI inspection is not applicable because no screens changed. Independent review is in progress.
- 2026-11-10 review round 1: 0 P0, 1 P1. A PHP file can exist without any discovered tests; the runner must fail that case before recording a milestone pass. Added a real-Pest regression, then require `--fail-on-empty-test-suite` in the PHP invocation. Original business assertions remain unchanged.
- 2026-11-10 review round 2: 0 P0, 0 P1. Real-Pest empty-suite regression failed before the fix (exit 0), then passed after it (exit 1, no partial pass recorded). Final `CI=true composer check` passed 264 PHP tests / 1005 assertions and six frontend tests; final `CI=true composer check:reference` passed 312 / 1162 and six frontend tests. `composer validate --no-check-publish` and `git diff --check` passed. M1 is ready to commit; M2 publication remains open.

## Surprises & Discoveries
- The stop hook currently requires a full check even between milestones. Separate partial validation is required to make the approved workflow usable without weakening final acceptance.

## Decision Log
- 2026-11-10 The user approved the workflow change and instructed implementation, commit, push and Packagist release. This authorizes edits to AGENTS.md, ARCHITECTURE.md, docs/workflow.md, docs/PLANS.md, docs/golden-principles.md, docs/rules/tests.md, docs/prompts/cleanup.md, docs/prompts/upgrade.md, composer.json, scripts/hooks/finish-check.php, scripts/check-milestone.php, tests/Unit/Hooks/MilestoneCheckTest.php and tests/Structure/ConfigTest.php as needed to implement the agreed validation boundary. CLAUDE.md already imports AGENTS.md and needs no duplicate policy.
- 2026-11-10 Add `composer check:static` as the shared static phase and `composer check:milestone -- <test paths>` as the partial runner. Update ConfigTest's expected script composition because the user changed the validation workflow; preserve its full-check assertions and enforce the new entry points as well. Existing business test assertions remain unchanged.
- 2026-11-10 Publish v1.1.0: the public package is v1.0.1, and this is a workflow behavior change. Keep legacy unpublished v2.x tags untouched. No dependency changes are needed.

## Outcomes & Retrospective
Implementation and publication are in progress.
