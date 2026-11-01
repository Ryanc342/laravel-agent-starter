# Review prompt

Used by the `reviewer` subagent (.codex/agents/reviewer.toml, .claude/agents/reviewer.md), or pasted into a new conversation.

---

You did not write this change. Review it with fresh eyes against its plan.

1. Read the plan in `docs/exec-plans/active/` (or the file the user names). Its "Agreed decisions" are the specification.
2. Read the diff: `git diff main...HEAD`, plus untracked files from `git status`.
3. Read docs/golden-principles.md and the "Code Review Rules" section of AGENTS.md. The `[review]` rules are yours: no machine checks them.
4. For each agreed decision, find the file and method that implement it.
5. List the `[review]` rules of docs/golden-principles.md that the change touches (it adds or changes code the rule is about), in the order the file lists them, each with `ok` or the finding. Leave out the rules it does not touch. Then search for the four deviations missed most often, whatever the change looks like, and paste for each the search you ran and what it found. A search you did not paste counts as not done.
    - A rule written twice. For each condition, default value, constant or lookup in the change (a time zone, "defaults to 0", "a record the current user owns"), search the whole of app/ and resources/js for a second copy, including in another Action that does the same lookup.
    - React deciding a business rule. In every changed page and component, look at each text, button or style that depends on a flag: `!item.can.delete` shown as "still in use", `can.delete && !is_deletable`, a status or date compared under a new name. A `can.*` flag already includes the business state (the button shows exactly when pressing it succeeds), and the reason for a refusal comes from the Resource as text.
    - A lock where nothing collides. For each `lockForUpdate()` or `sharedLock()` in the change, name the two writes that could reach the same row at the same moment. A page or an Action that only reads never locks.
    - Hand-written formatting or parsing. Search the change for dates, money, numbers or text taken apart or put together by hand (string slicing, splitting on "." or "-", building "RM 1,000.00"), where PHP, Laravel, Brick\Math or Intl already provide it.
6. Report P0 and P1 findings, each as `[P0|P1] path:line`, what is wrong, the rule it breaks, and which owner should hold it:
    - P0: wrong behaviour, data loss, a permission hole, a decision implemented differently from the plan.
    - P1: a `[review]` rule in docs/golden-principles.md broken in the changed code, behaviour not in the plan, or a missing or weakened test.
7. Then one line on the plan: which decisions are implemented, which are missing, and anything outside the plan.
8. Skip style and naming. A copied block of code and hand-written formatting are not style: they break `[review]` rules, so they are P1. Do not suggest new abstractions. Do not edit files.

End with `No P0/P1 findings.` when there are none. This is round <n> of at most 3.
