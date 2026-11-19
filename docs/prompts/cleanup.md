# Cleanup prompt

Run in a new conversation for the whole repository when the user asks for it. A completed plan has no cleanup of its own (docs/workflow.md, Cleanup). Work in a new branch or worktree, unless the user says to commit on the current branch.

---

Do the cleanup pass on this repository. Change nothing that is not listed below.

1. Read docs/golden-principles.md, ARCHITECTURE.md and every file in .claude/rules/.
2. For each business domain under app/Actions, scan the whole domain (Actions, Models, Enums, Policies, Resources, controllers, routes, tests, React pages and components, translations). Go through every `[review:<lane>]` rule of .claude/rules/ lane by lane, in the order of the lane table of docs/golden-principles.md, one rule at a time, each with `ok` or the deviation. Use the `explorer` subagent for large scans.
3. Write the plan docs/exec-plans/active/cleanup-<date>.md (docs/PLANS.md): the deviations you will fix are its milestones. Running this cleanup is the user's "开始" for these fixes, so do not wait.
4. Fix each deviation in its own small commit: move the rule to its owner, make the other places call it, update the tests, run `composer check:milestone -- <affected test paths>` and add the Progress line (docs/workflow.md, step 3). Run full `composer check` when the cleanup plan is implemented. Change no dependency, no check, no existing test assertion and no behaviour: a fix that would change one is listed instead.
5. Check the docs against the code: paths, method names and examples in AGENTS.md, ARCHITECTURE.md and docs/ that no longer exist. List each one that is out of date, with the fix it needs: the user decides.
6. Look for gaps in the machine checks only. For each `[PHPStan]`, `[oxlint]` and `[Structure]` rule, look for code that breaks it although `composer check` passes. List each gap with the file and line that shows it and a suggested change to that check. Do not suggest machine checks for `[review:<lane>]` rules: they stay with the reviewers. A check may only get stricter. In a project made from this template, write the suggestion for the template, so the user can bring it there.
7. List rules that say the same as another, and rules that no finding in docs/exec-plans/completed/ has named in the last three months, each with a suggestion to merge or remove. Also list each file over 300 lines that does more than one job, with how to split it by job (never by size alone), and the rule gaps in the Outcomes & Retrospective of the plans completed since the previous cleanup plan in docs/exec-plans/completed/, each with a suggested rule for the user to decide.
8. Run the final review of docs/workflow.md, step 4, for every lane the fixes touch.
9. Update docs/exec-plans/tech-debt.md (add what you could not fix, remove what you fixed). Write the lists of steps 4 to 7 into the plan's Outcomes & Retrospective, with the files they name, and move the plan to docs/exec-plans/completed/.
10. Report (docs/workflow.md, step 5): the fixes, and in business words what the lists of steps 4 to 7 are about, for the user to decide; the plan holds the details.
