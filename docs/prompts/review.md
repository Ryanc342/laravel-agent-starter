# Review prompt

Used by the `reviewer` subagent (.codex/agents/reviewer.toml, .claude/agents/reviewer.md), or pasted into a new conversation.

---

You did not write this change. Review it with fresh eyes against its plan.

1. Read the plan in `docs/exec-plans/active/` (or the file the user names). Its "Agreed decisions" are the specification.
2. Read the diff: `git diff main...HEAD`, plus untracked files from `git status`.
3. Read docs/golden-principles.md and the "Code Review Rules" section of AGENTS.md. The `[review]` rules are yours: no machine checks them.
4. For each agreed decision, find the file and method that implement it.
5. Go through the `[review]` rules one by one, in the order docs/golden-principles.md lists them. Write one line for every rule: `ok`, `n/a` (the change touches nothing it is about), or the finding. Do not skip a rule because the change looks fine. Two rules are missed most often, so search for them:
    - A rule written twice. For each condition, default value, constant or lookup in the change (a time zone, "defaults to 0", "a record the current user owns"), search the whole of app/ and resources/js for a second copy, including in another Action that does the same lookup.
    - React deciding a business rule. In every changed page and component, look at each text, button or style that depends on a flag: `!item.can.delete` shown as "still in use", `can.delete && !is_deletable`, a status or date compared under a new name. A `can.*` flag already includes the business state (the button shows exactly when pressing it succeeds), and the reason for a refusal comes from the Resource as text.
6. Report P0 and P1 findings, each as `[P0|P1] path:line`, what is wrong, the rule it breaks, and which owner should hold it:
    - P0: wrong behaviour, data loss, a permission hole, a decision implemented differently from the plan.
    - P1: a `[review]` rule in docs/golden-principles.md broken in the changed code, behaviour not in the plan, or a missing or weakened test.
7. Then one line on the plan: which decisions are implemented, which are missing, and anything outside the plan.
8. Skip style and naming. A copied block of code and hand-written formatting are not style: they break `[review]` rules, so they are P1. Do not suggest new abstractions. Do not edit files.

End with `No P0/P1 findings.` when there are none. This is round <n> of at most 3.
