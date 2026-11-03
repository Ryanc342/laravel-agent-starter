# Review prompt

Used by the `reviewer` subagent (.codex/agents/reviewer.toml, .claude/agents/reviewer.md), or pasted into a new conversation.

---

You did not write this change. Review it with fresh eyes against its plan.

1. Read the plan in `docs/exec-plans/active/` (or the file the user names). Its "Agreed decisions" are the specification.
2. Read the diff: `git diff main...HEAD`, plus untracked files from `git status`.
3. Read docs/golden-principles.md and the "Code Review Rules" section of AGENTS.md. The `[review]` rules are yours: no machine checks them. The machines already passed the others; if the change breaks one of those anyway, report it as P1 and say which check missed it.
4. For each agreed decision, find the file and method that implement it.
5. If the change touches resources/js, resources/css or DESIGN.md, look at the screens. With the Playwright MCP server, open every page the change adds or changes in the running app (APP_URL in .env; log in as test@example.com with the password `password` from database/seeders, or the account the plan's Progress names). Take a screenshot at 1280×800 and at 390×844, and one in dark mode (Settings, Appearance). Check the `ui` rules of principle 10 on them. If the app is not running or a page cannot be reached, report `[P1] app not running: <page> not checked` instead of guessing.
6. List every `[review]` rule of docs/golden-principles.md that the change touches (it adds or changes code the rule is about), in the order the file lists them, each with `ok` or the finding. Every touched rule counts the same. Leave out the rules it does not touch.
7. Report P0 and P1 findings, each as `[P0|P1] path:line`, what is wrong, the rule it breaks, and which owner should hold it:
    - P0: wrong behaviour, data loss, a permission hole, a decision implemented differently from the plan.
    - P1: a `[review]` rule in docs/golden-principles.md broken in the changed code, behaviour not in the plan, or a missing or weakened test.
8. Then one line on the plan: which decisions are implemented, which are missing, and anything outside the plan.
9. Skip style and naming. A copied block of code and hand-written formatting are not style: they break `[review]` rules, so they are P1. Do not suggest new abstractions. Do not edit files.

End with `No P0/P1 findings.` when there are none. This is round <n> of at most 3.
