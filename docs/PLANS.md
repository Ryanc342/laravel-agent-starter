# ExecPlans

An ExecPlan is the written plan for one feature. It lives in `docs/exec-plans/active/<feature>.md` while the work is open and moves to `docs/exec-plans/completed/` when it is done. How a plan is written, approved and worked through: docs/workflow.md.

## Rules

- **Self-contained.** Someone who has only this file and the repository can continue the work. Do not rely on the chat history.
- **Living.** Tick each milestone when it is done and add its Progress line: one line per milestone or review round, with the date and the commit, no time of day. A review round is one summary line; the review ledger keeps its runs.
- **Milestones are small.** Each one takes about an hour, is a few hundred lines and ends in a commit.
- **Approved changes.** The protected files the plan changes (AGENTS.md "Red lines"), the dependencies it adds or upgrades, and the existing tests whose assertions change, each with the decision that changes the requirement. The user's "开始" approves exactly this list.
- **A preview for pages.** A plan that adds or changes a page names its static previews in an agreed decision: "The pages look like the accepted previews: <files>", PNG files at 1280×800 and 390×844 under `storage/app/private/design-previews/<plan file name>/` (docs/workflow.md, step 2).
- **A walk list for pages.** A plan with pages lists its walk under Validation: one line per flow and role, `W1 <role> (<test account>): <flow, from its first step to its last>`, for every flow the plan adds or changes, with only the steps a browser can do. A step it cannot do (a mail arrives, a scheduled job runs) names the test that covers it instead. database/seeders/DatabaseSeeder.php creates one test account per role.

The sections below are required, with exactly these headings. The finish check reads Purpose, Agreed decisions and Milestones; a plan that still holds this template there is no plan.

## Template

```markdown
# <Feature name>

## Purpose
What the user gets and why, in one or two sentences.

## Agreed decisions
1. <decision from the questions>
2. <decision> (differs from <rule>)
3. The pages look like the accepted previews: <files> (a plan with pages)

## Approved changes
- Protected files: <path> (decision <n>), or none
- Dependencies: <package and its manifest> (decision <n>), or none
- Existing test assertions that change: <test> (decision <n>), or none

## Milestones
- [ ] M1 <layer or slice>: <what is built> + <tests>
- [ ] M2 <layer or slice>: <what is built> + <tests>
- [ ] M3 ...

## Validation
- Per milestone: `composer check:milestone -- <affected test paths>`
- Final: full `composer check`
- <test names or behaviours that prove each decision>
- W1 <role> (<test account>): <flow, from its first step to its last>
- W2 <role> (<test account>): <flow>; <a step a browser cannot do>: <the test that covers it>

## Progress
- <YYYY-MM-DD> <milestone or review round>, commit <hash>

## Surprises & Discoveries
- <something you found that changed the work>

## Decision Log
- <YYYY-MM-DD> <a change of requirements the user made after the plan, approved with a new "开始">

## Outcomes & Retrospective
<Filled in at the end: what was delivered and what is left.>
Rule gaps:
- <a case no rule settled, from the report> ("for the template" when it belongs there)
```
