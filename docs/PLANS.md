# ExecPlans

An ExecPlan is the written plan for one feature. It lives in `docs/exec-plans/active/<feature>.md` while the work is open and moves to `docs/exec-plans/completed/` when it is done.

## Rules

- **Self-contained.** Someone who has only this file and the repository can continue the work. Do not rely on the chat history.
- **Living.** Update it as you go: tick milestones, add Progress lines with a timestamp, log decisions and surprises. The file always shows the current state.
- **Milestones are small.** Each one takes about an hour, is a few hundred lines, passes full static checks and tests for its affected scope, and ends in a commit. The completed implementation passes full `composer check`; shared infrastructure or unbounded impact needs an earlier full check (docs/workflow.md).
- **No review between milestones.** No milestone has a review round, whatever their number: every lane is reviewed in the final review (docs/workflow.md, step 4). A `(review)` mark on a milestone line asks for nothing.
- **Decisions come from the user.** "Agreed decisions" lists the answers from the grilling. Anything new goes back to the user as a question.
- **Validation is concrete.** For each milestone, name affected behavior and callers (including unchanged tests), selected test paths, commands and expected results. Record partial validation accurately. Name the final full check separately, plus any reason to run it early. A plan with pages lists its walk under Validation: one numbered line per flow and role, `W1 <role>: <flow, from its first step to its last>`, for every flow the plan adds or changes. Both seats of the `behaviour` lane walk the same list (docs/workflow.md, step 4).

The sections below are required, with exactly these headings. The finish check (scripts/hooks/finish-check.php) sends back a plan whose Purpose, Agreed decisions or Milestones are empty, and a completed plan without a `composer check` result and a review round in Progress or with an empty Outcomes & Retrospective.

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
- [ ] M2 <layer or slice>: <what is built> + <tests>
- [ ] M3 ...

## Validation
- Per milestone: `composer check:milestone -- <affected test paths>` passes; record scope and reason
- Final implementation: full `composer check` passes, repeated after any later code change
- <test names or behaviours that prove each decision>
- <page check, if there is UI: the final review's `look` and `behaviour` rounds (docs/workflow.md, step 4)>
- W1 <role>: <flow, from its first step to its last>
- W2 <role>: <flow>

## Progress
- <YYYY-MM-DD HH:MM> <what was done>, affected scope <paths/reason>, <command/result>, commit <hash>

## Surprises & Discoveries
- <something you found that changed the work>

## Decision Log
- <YYYY-MM-DD> <decision taken during the work> because <reason>

## Outcomes & Retrospective
<Filled in at the end: what was delivered, what is left, what to add to docs or checks.>
```
