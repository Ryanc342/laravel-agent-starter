# Cleanup prompt

Run in a new conversation once a week for the whole repository (or as a scheduled task). A completed plan has no cleanup of its own (docs/workflow.md, Cleanup). Work in a new branch or worktree, unless the user says to commit on the current branch.

---

Do the weekly cleanup pass on this repository. Change nothing that is not listed below.

1. Read docs/golden-principles.md, ARCHITECTURE.md and docs/QUALITY.md.
2. For each business domain under app/Actions, scan the whole domain (Actions, Models, Enums, Policies, Resources, controllers, routes, tests, React pages and components, translations). Go through all 105 `[review]` rules of docs/golden-principles.md lane by lane, in the order of "Review lanes" (`business`, `input`, `frontend`, `tests`, `look`, `behaviour`), one rule at a time, each with `ok` or the deviation. Use the `explorer` subagent for large scans.
3. Write the plan docs/exec-plans/active/cleanup-<date>.md (docs/PLANS.md): the deviations you will fix are its milestones. Running this cleanup is the user's "start" for these fixes, so do not wait.
4. Fix each deviation in its own small commit: move the rule to its owner, make the other places call it, update the tests, run `composer check:milestone -- <affected test paths>` and add the Progress line (docs/workflow.md, step 3). Run full `composer check` when the cleanup plan is implemented. Do not change behaviour. If a fix would change behaviour, list it instead.
5. Check the docs against the code: paths, method names and examples in AGENTS.md, ARCHITECTURE.md and docs/ that no longer exist. Fix the docs, not the code. Running this cleanup is the user's approval for that, and only for that: name each doc you fix in the plan's Decision Log. A change to what a rule says, or to lint/ and check configs, is listed for the user instead.
6. Look for gaps in the 18 machine checks only. For each `[PHPStan]`, `[oxlint]` and `[Structure]` rule, look for code that breaks it although `composer check` passes. List each gap with the file and line that shows it and a suggested change to that check. Do not suggest machine checks for `[review]` rules: they stay with the reviewers. Change nothing in lint/, tests/Structure/ or the check configs: the user decides, and a check may only get stricter. In a project made from this template, write the suggestion for the template, so the user can bring it there.
7. List rules that say the same as another, and rules that no finding in docs/exec-plans/completed/ has named in the last three months, each with a suggestion to merge or remove. The user decides; change nothing.
8. Ask `reviewer-opus` and `reviewer-haiku` to review the fixes: one run per lane the fixes touch, in the rhythm of docs/workflow.md steps 3 and 4 (docs/prompts/review.md).
9. Update docs/QUALITY.md (grade and notes per domain, today's date) and docs/exec-plans/tech-debt.md (add what you could not fix, remove what you fixed). Fill in the plan's Outcomes & Retrospective and move it to docs/exec-plans/completed/.
10. Report: the rule list from step 2, one line per fix with its commit, the deviations you listed instead of fixing, the check gaps from step 6, the rule suggestions from step 7, and the grade changes.
