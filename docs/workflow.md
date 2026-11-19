# Workflow

Every request goes through the same steps: questions, plan, build, review, report. The user answers questions only while the plan is written. After the user's "开始" you ask nothing until the report. When you are about to stop, the finish check (scripts/hooks/finish-check.php) sends back a missing step: do it.

## 1. Questions

Look up yourself what the code, the database (Boost MCP), the template rules and the docs answer, and never ask the user for it. Ask only what they cannot answer: what to build, and every decision about money, permissions, who can see which data, or deleting or changing real data. When nothing is open, write the plan without asking.

Ask in rounds: the questions whose prerequisites are settled, all at once, then wait for the answers before the next round. Write each round in the user's language and in plain business words, in chat text: one line saying what you looked up and how many questions the round has, then each question on its own line followed by its recommendation on its own line:

```
I checked the code: <facts found>. Round 1, 3 questions.

Q1 <short title>: <question, with choices if any>
Recommended: <your recommended answer and the reason>
Q2 <short title>: <question>
Recommended: <answer and reason>
```

Word each question so "yes" accepts your recommendation; the user may answer by number ("1 yes 2 no, because..."). Ask only about what this part builds: a rule for a status a later part brings is not asked or planned now. If the scope is too big, propose splitting it first. For large lookups use the read-only `explorer` subagent with a fixed file list and a capped answer.

## 2. Plan

Write `docs/exec-plans/active/<feature>.md` following docs/PLANS.md. "Agreed decisions" lists every answer. When a decision differs from a template rule, write which rule next to it, and say so when you hand in the plan. Look up the protected docs and checks the change will affect, and list them in "Approved changes" with the dependencies it adds or upgrades and the existing tests whose assertions change, each with its decision.

Then stop: change no code until the user says "开始" (also `开始吧`, `好开始`, `好的开始`, `ok开始` or `start`, with any spaces and punctuation). That message takes the snapshot of the plan the checks and the reviewers read; a new "开始" after a change of requirements takes a new one. What you write into the plan afterwards changes nothing that was approved.

## 3. Build

After "开始" you ask nothing. Whatever the plan does not say follows the template rules, or keeps the current behaviour where no rule settles it, and goes into the report. A case about money, permissions, who can see which data, or deleting or changing real data keeps the current behaviour and goes into the report for the user to decide. A protected file or dependency that "Approved changes" does not name is not changed: name it in the report.

For each milestone:

1. Search for what already exists. For every Action, scope, enum method, model method, component or hook you are about to create, search first, including the underlying conditions (fields, enum cases, query conditions), not only helper names: `rg -n "function \w+\(" app/Enums app/Models app/Policies app/Actions`. If a rule already exists in the wrong place, move it in this change instead of adding a copy.
2. Place every rule using the owner table in ARCHITECTURE.md.
3. Copy the structure of the matching file in docs/reference/tasks/.
4. Write the tests first (Pest, .claude/rules/tests.md) and run them: they must fail. Then implement in this order: migration, enum, model and factory, policy, action, form request, resource and TypeScript type, controller and routes, React. Add new pages to `mainNavItems` in `resources/js/components/app-sidebar.tsx`. For a page, follow DESIGN.md and .claude/rules/visual-design.md; the browser check is the final review's screen rounds.
5. Run `composer check:milestone -- <affected test files or directories>` and fix every failure.
6. Commit, tick the milestone and add its Progress line (docs/PLANS.md).

Before the next milestone, search the code already written in this task for the same condition you are about to write. When every milestone is done, run full `composer check`.

## 4. Review

All review happens once, in the final review, after full `composer check`. Reviews are runs of the two reviewer subagents, `reviewer-opus` and `reviewer-haiku` (docs/prompts/review.md); they run in the foreground, so the session waits for their answers.

Commit before each round: a round of uncommitted code does not count. A round is, for each lane the plan's changes touch that is not done yet (docs/golden-principles.md, Review lanes), one run of each reviewer, all started in one message on the same commit. Before the round run `php scripts/hooks/finish-check.php round <plan path>`: it writes the plan's diff and the plan as approved, and prints `Diff: <file>` and `Snapshot: <file>`. Each request is `Lane: <lane>. Round: <n>. Plan: <plan path>. Diff: <file>. Snapshot: <file>.` The rounds of a plan are numbered 1, 2, 3.

Fix every finding the reviewers report, commit, and run the next round for the lanes not done. A lane is done when, in its latest round, both reviewers report no finding on committed code and nothing in the lane's places changed since. A later commit that changes only docs/ or *.md keeps the round; a code change voids only the lanes its files touch. What a reviewer lists as a rule gap, and each decision that differs from a rule, goes into the report; a problem it lists in older code goes into docs/exec-plans/tech-debt.md and the report. Neither adds a round. After every fourth round of a lane that is still not done, say so in one line (the lane, its rounds, what is open) and go on.

The screens come last: the lanes `look` and `behaviour` start when every other lane the plan touches is done. Before each screen round run `scripts/screen-round` (`scripts/screen-round --reference` when the plan's changes touch docs/reference/tasks/). It prints two addresses, one copy of the app at the commit under review for each reviewer. Start both reviewers at once, each with its address: `Lane: <lane>. Round: <n>. App: <url>. Plan: <plan path>. Diff: <file>. Snapshot: <file>.`, the first address for `reviewer-opus`, the second for `reviewer-haiku`. Inside a seat `behaviour` runs first and `look` after it, because the walk makes the data the pages show. A `behaviour` run that did not walk every item of the walk list counts as not run: fix what it reported, run `scripts/screen-round` again, and that reviewer walks again. When a screen finding leads to a code fix, the lanes its files touch are reviewed again, the other lanes first and the screens after them.

If the user pastes review findings, check each one, move the rule to its one owner, update its callers and tests, and verify again.

## 5. Report

Report in the user's language and in business words, with no file or method names:

- what was built, decision by decision;
- the check results: `composer check`, and `composer check:reference` when it ran;
- the rule gaps: cases no rule settled, and each decision that differs from a rule;
- what waits for the user: the cases kept as they were because they are the user's to decide, and the protected changes the plan did not approve.

Include screenshots only if the user explicitly asked to keep them. Fill in "Outcomes & Retrospective", add what you deferred to docs/exec-plans/tech-debt.md, and update that domain's row in docs/QUALITY.md. Move the plan to `docs/exec-plans/completed/` last, when `php scripts/hooks/finish-check.php review <plan path>` prints every lane as done.

## A new conversation

Read docs/exec-plans/active/ and continue from the plan's Progress. "继续" continues the work without first reporting open steps.

## Parallel work

One plan, one git worktree: `scripts/worktree-up <name>` creates it with its own `.env`, database and port. Do not work on two plans in the same folder.

## Cleanup

Once a week, run docs/prompts/cleanup.md in a new conversation for the whole repository.
