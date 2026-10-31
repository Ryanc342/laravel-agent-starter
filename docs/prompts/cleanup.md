# Weekly cleanup prompt

Run once a week in a new conversation (or as a scheduled task). Work in a new branch or worktree.

---

Do a cleanup pass on this repository. Change nothing that is not listed below.

1. Read docs/golden-principles.md, ARCHITECTURE.md and docs/QUALITY.md.
2. For each business domain under app/Actions, scan the whole domain (Actions, Models, Enums, Policies, Resources, controllers, React pages and components) for deviations from the golden principles. The most common one: the same condition on the same fields, enum cases or roles written in two places. Use the `explorer` subagent for large scans.
3. Fix each deviation in its own small commit: move the rule to its owner, make the other places call it, update the tests, run `composer check`. Do not change behaviour. If a fix would change behaviour, list it instead.
4. Check the docs against the code: paths, method names and examples in AGENTS.md, ARCHITECTURE.md and docs/ that no longer exist. Fix the docs, not the code.
5. Update docs/QUALITY.md (grade and notes per domain, today's date) and docs/exec-plans/tech-debt.md (add what you could not fix, remove what you fixed).
6. Report: one line per fix with its commit, the deviations you listed instead of fixing, and the grade changes.
