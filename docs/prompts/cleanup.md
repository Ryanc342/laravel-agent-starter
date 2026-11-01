# Weekly cleanup prompt

Run once a week in a new conversation (or as a scheduled task). Work in a new branch or worktree, unless the user says to commit on the current branch.

---

Do a cleanup pass on this repository. Change nothing that is not listed below.

1. Read docs/golden-principles.md, ARCHITECTURE.md and docs/QUALITY.md.
2. For each business domain under app/Actions, scan the whole domain (Actions, Models, Enums, Policies, Resources, controllers, React pages and components). List the `[review]` rules of docs/golden-principles.md that apply to the domain's code, each with `ok` or the deviation, and run the four searches of docs/prompts/review.md step 5, pasting each search and what it found. Use the `explorer` subagent for large scans.
3. Write the plan docs/exec-plans/active/cleanup-<date>.md (docs/PLANS.md): the deviations you will fix are its milestones. Running this cleanup is the user's "start" for these fixes, so do not wait.
4. Fix each deviation in its own small commit: move the rule to its owner, make the other places call it, update the tests, run `composer check`, add a Progress line. Do not change behaviour. If a fix would change behaviour, list it instead.
5. Check the docs against the code: paths, method names and examples in AGENTS.md, ARCHITECTURE.md and docs/ that no longer exist. Fix the docs, not the code. Running this cleanup is the user's approval for that, and only for that: name each doc you fix in the plan's Decision Log. A change to what a rule says, or to lint/ and check configs, is listed for the user instead.
6. Ask the `reviewer` subagent to review the fixes (docs/prompts/review.md, docs/workflow.md step 4).
7. Update docs/QUALITY.md (grade and notes per domain, today's date) and docs/exec-plans/tech-debt.md (add what you could not fix, remove what you fixed). Fill in the plan's Outcomes & Retrospective and move it to docs/exec-plans/completed/.
8. Report: the rule list from step 2, one line per fix with its commit, the deviations you listed instead of fixing, and the grade changes.
