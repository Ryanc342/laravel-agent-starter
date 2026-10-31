# ExecPlans

An ExecPlan is the written plan for one feature. It lives in `docs/exec-plans/active/<feature>.md` while the work is open and moves to `docs/exec-plans/completed/` when it is done.

## Rules

- **Self-contained.** Someone who has only this file and the repository can continue the work. Do not rely on the chat history.
- **Living.** Update it as you go: tick milestones, add Progress lines with a timestamp, log decisions and surprises. The file always shows the current state.
- **Milestones are small.** Each one takes about an hour, is a few hundred lines, passes `composer check` on its own, and ends in a commit.
- **Decisions come from the user.** "Agreed decisions" lists the answers from the grilling. Anything new goes back to the user as a question.
- **Validation is concrete.** Name the commands and what must be seen (test names, a page state, a value in the database).

The sections below are required, with exactly these headings. `tests/Structure/DocsTest.php` checks every plan in `active/` and `completed/`.

## Template

```markdown
# <Feature name>

## Purpose
What the user gets and why, in one or two sentences.

## Agreed decisions
1. <decision from the grilling>
2. <decision>

## Milestones
- [ ] M1 <layer or slice>: <what is built> + <tests>
- [ ] M2 ...

## Validation
- `composer check` passes
- <test names or behaviours that prove each decision>
- <page check with Playwright, if there is UI>

## Progress
- <YYYY-MM-DD HH:MM> <what was done>, commit <hash>

## Surprises & Discoveries
- <something you found that changed the work>

## Decision Log
- <YYYY-MM-DD> <decision taken during the work> because <reason>

## Outcomes & Retrospective
<Filled in at the end: what was delivered, what is left, what to add to docs or checks.>
```
