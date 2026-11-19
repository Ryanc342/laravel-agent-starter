# Cleanup prompt

Run in a new conversation once a week for the whole repository (or as a scheduled task). A completed plan has no cleanup of its own (docs/workflow.md, Cleanup). Work in a new branch or worktree, unless the user says to commit on the current branch.

---

Do the weekly cleanup pass on this repository. Change nothing that is not listed below.

1. Read docs/golden-principles.md, ARCHITECTURE.md and docs/QUALITY.md.
2. For each business domain under app/Actions, scan the whole domain (Actions, Models, Enums, Policies, Resources, controllers, routes, tests, React pages and components, translations). Go through every `[review]` rule of docs/golden-principles.md lane by lane, in the order of its lane sections, one rule at a time, each with `ok` or the deviation. Use the `explorer` subagent for large scans.
3. Write the plan docs/exec-plans/active/cleanup-<date>.md (docs/PLANS.md): the deviations you will fix are its milestones, and its "Approved changes" are none. Running this cleanup is the user's "开始" for these fixes, so do not wait.
4. Fix each deviation in its own small commit: move the rule to its owner, make the other places call it, update the tests, run `composer check:milestone -- <affected test paths>` and add the Progress line (docs/workflow.md, step 3). Run full `composer check` when the cleanup plan is implemented. Change no protected file (AGENTS.md "Red lines") and no behaviour: a fix that would change either is listed instead.
5. Check the docs against the code: paths, method names and examples in AGENTS.md, ARCHITECTURE.md and docs/ that no longer exist. The docs are protected: list each one that is out of date, with the fix it needs.
6. Look for gaps in the machine checks only. For each `[PHPStan]`, `[oxlint]` and `[Structure]` rule, look for code that breaks it although `composer check` passes. List each gap with the file and line that shows it and a suggested change to that check. Do not suggest machine checks for `[review]` rules: they stay with the reviewers. A check may only get stricter. In a project made from this template, write the suggestion for the template, so the user can bring it there.
7. List rules that say the same as another, and rules that no finding in docs/exec-plans/completed/ has named in the last three months, each with a suggestion to merge or remove.
8. Run the final review of docs/workflow.md, step 4, for every lane the fixes touch.
9. Update docs/QUALITY.md (grade and notes per domain, today's date) and docs/exec-plans/tech-debt.md (add what you could not fix, remove what you fixed). Write the lists of steps 4 to 7 into the plan's Outcomes & Retrospective, with the files they name, and move the plan to docs/exec-plans/completed/.
10. Report (docs/workflow.md, step 5): the fixes, the grade changes, and in business words what the lists of steps 4 to 7 are about, for the user to decide; the plan holds the details.
