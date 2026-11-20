# Workflow

Every request goes through the same steps: questions, plan, build, review, report. The user answers questions only while the plan is written. After the user's "开始" you ask nothing until the report. When you are about to stop after changing code, the finish check (scripts/hooks/finish-check.php) sends you back once if no check passed on that code: run it.

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

Write `docs/exec-plans/active/<feature>.md` following docs/PLANS.md. "Agreed decisions" lists every answer. When a decision differs from a template rule, write which rule next to it, and say so when you hand in the plan. A dependency the plan adds or upgrades, a check it changes, and an existing test assertion it changes are each an agreed decision of their own.

A plan that adds or changes a page has a static preview, made now: a picture of each new or changed page at 1280×800 and at 390×844, in the template's design (DESIGN.md, the page's pattern file and its sample, docs/design) with example data. The previews are not committed and are not app code:

1. Write each page as a static HTML file in `storage/app/private/design-previews/<plan file name>/`, next to a copy of `public/build` (run `npm run build` first), linking the two stylesheets `public/build/manifest.json` and `fonts-manifest.json` name (`/build/assets/…`). Use only the theme's classes the app already uses: the built CSS holds no other.
2. Serve the folder with `php -S 127.0.0.1:<free port> -t <folder>` (the browser refuses `file:` pages), open each page in the `playwright` browser, and take the screenshots at both sizes. Copy each PNG from the browser's temporary folder (`.playwright-mcp/session-*/`) into the preview folder before you close the browser, then stop the server.
3. Name the files in an agreed decision ("The pages look like the accepted previews: <files>"), look at them yourself, and name them when you hand in the plan. The user's "开始" accepts them with the plan.

Then stop: change no code until the user says "开始" (also `开始吧`, `好开始`, `好的开始`, `ok开始` or `start`, with any spaces and punctuation). That message approves the plan as it is then, its previews included; a change of requirements afterwards goes into the Decision Log and needs a new "开始". What you write into the plan afterwards changes nothing that was approved.

## 3. Build

After "开始" you ask nothing. Whatever the plan does not say follows the template rules, or keeps the current behaviour where no rule settles it, and goes into the report. A case about money, permissions, who can see which data, or deleting or changing real data keeps the current behaviour and goes into the report for the user to decide. A dependency, a check or an existing test assertion the plan does not name is not changed: name it in the report.

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

Commit before each round: a round of uncommitted code does not count. A round is, for each lane the plan's changes touch that is not done yet (docs/golden-principles.md, Review lanes: `backend`, `frontend`, `screens`), one run of each reviewer, all started in one message on the same commit: at most six runs. Before the round run `php scripts/hooks/finish-check.php round <plan path>`: it writes the plan's diff and a copy of the plan, and prints `Diff: <file>` and `Plan: <file>`. Each request is `Lane: <lane>. Round: <n>. Plan: <file>. Diff: <file>.` The rounds of a plan are numbered 1, 2, 3.

Fix every finding the reviewers report, commit, and run the next round for the lanes not done. A finding names one place; fix every problem of the same kind in this change, not only that place: search the change for the same break before you commit, so the next round does not find it one place at a time. A lane is done when, in its latest round, both reviewers report no finding, at any level, on committed code and nothing in the lane's places changed since. A later commit that changes only docs/ or *.md, apart from the code of the reference feature and of the sample copies, keeps the round; a code change voids only the lanes its files touch. Each round is one Progress line in the plan: the round, the commit, and the findings per lane, reviewer and level. What a reviewer lists as a rule gap, and each decision that differs from a rule, goes into the report; a problem it lists in older code goes into docs/exec-plans/tech-debt.md and the report. Neither adds a round. After every fourth round of a lane that is still not done, say so in one line (the lane, its rounds, what is open) and go on.

The screens come last: the lane `screens` starts when every other lane the plan touches is done. Only `reviewer-opus` looks at pages. Before each screen round run `scripts/screen-round` (`scripts/screen-round --reference` when the plan's changes touch docs/reference/tasks/). It prints one address, a copy of the app at the commit under review. Start both reviewers at once: `reviewer-opus` with `Lane: screens. Round: <n>. App: <url>. Plan: <file>. Diff: <file>.`, which walks every item of the walk list and then looks at the pages, and `reviewer-haiku` without the address, which reads the code. A run of `reviewer-opus` that did not walk every item of the walk list counts as not run: fix what it reported, run `scripts/screen-round` again, and it walks again. When a screen finding leads to a code fix, the lanes its files touch are reviewed again, the other lanes first and the screens after them.

If the user pastes review findings, check each one, move the rule to its one owner, update its callers and tests, and verify again.

## 5. Report

Report in the user's language and in business words, with no file or method names:

- what was built, decision by decision;
- the check results: `composer check`, and `composer check:reference` when it ran;
- the review: its rounds and the findings they fixed, by level (docs/golden-principles.md, Levels);
- the rule gaps: cases no rule settled, and each decision that differs from a rule;
- what waits for the user: the cases kept as they were because they are the user's to decide, and the dependencies, checks and test assertions the plan did not name.

Include screenshots only if the user explicitly asked to keep them. Delete the plan's previews (`storage/app/private/design-previews/<plan file name>/`) when it moves to completed/. Fill in "Outcomes & Retrospective" with what was delivered and the report's rule gaps, which stay with the plan in completed/ for the cleanup; in a project made from the template, mark a gap that belongs in the template "for the template". Add what you deferred to docs/exec-plans/tech-debt.md. Move the plan to `docs/exec-plans/completed/` last, when the plan's Progress shows every lane it touches as done.

## A new conversation

Read docs/exec-plans/active/ and continue from the plan's Progress. "继续" continues the work without first reporting open steps.

## Parallel work

One plan, one git worktree: `scripts/worktree-up <name>` creates it with its own `.env`, database and port. Do not work on two plans in the same folder.

## Cleanup

When the user asks for it, run docs/prompts/cleanup.md in a new conversation for the whole repository.
