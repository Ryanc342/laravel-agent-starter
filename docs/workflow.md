# Workflow

Every request goes through the same steps. The user steers; you do the work and bring evidence. When you are about to stop, the finish check (scripts/hooks/finish-check.php, principle 9 in docs/golden-principles.md) sends back any step that is missing: do it.

## 1. Grill every request

Based on Matt Pocock's `grilling` skill (mattpocock/skills). This is the default way of working for every request. There is no trigger and no exception. A request to fix lines in docs/exec-plans/tech-debt.md is a request too: "fix them all" or "commit on the current branch" says what to do, not "start".

Interview the user relentlessly until you reach a shared understanding. Map the request as a design tree: every decision branches into the decisions that hang off it.

Work the tree in rounds. The frontier is every decision whose prerequisites are already settled: the questions you can ask now without guessing at answers you have not heard yet. Ask the whole frontier in one round, then wait for the answers before the next round. A question whose answer depends on another question still open in this round belongs to a later round.

Format a round exactly like this: one line saying what you looked up and how many questions this round has, then each question on its own line followed by its recommendation on its own line. No icons or emoji, no dashes after the number, no blank lines or horizontal rules between questions:

```
I checked the code: <facts found>. Round 1, 3 questions.

Q1 <short title>: <question, with choices if any>
Recommended: <your recommended answer and the reason>
Q2 <short title>: <question>
Recommended: <answer and reason>
```

Write the round in the user's language. Word each question so "yes" accepts your recommendation. The user may answer by number ("1 yes 2 no, because...").

- Finding facts is your job. Look things up yourself (code, database through Boost MCP, the mobile app's calls) and never ask the user for anything you could find out. Do not block on a lookup: only the questions that depend on it wait.
- Decisions are the user's. Put each one to them and wait. Never answer your own decision questions. Decisions about money, permissions, or who can see which data always go to the user.
- Keep lookups cheap. Small lookups (a few files, one search) you do yourself. Large lookups (many files, long documents, many searches) go to the read-only `explorer` subagent with a fixed file list and a capped answer.
- Ask in plain business language, not code terms, in chat text, not a pop-up question tool. There is no cap on the number of questions. If the scope is too big, propose splitting it first.
- Ask only about what this part builds. A rule for a status or feature a later part brings ("only drafts can be edited" while drafts are the only status) is not asked or planned now: nothing could reach it, so it could only be tested with a stand-in.

The grilling is done when the frontier is empty: every branch visited, nothing silently assumed. If a request has no open decision, say so in one line with your understanding and ask the user to confirm. When the user says "enough, give me the plan", stop asking.

## 2. Write the plan

Write `docs/exec-plans/active/<feature>.md` following docs/PLANS.md. The "Agreed decisions" section lists every answer from the grilling. Then stop: change no code until the user says "start".

## 3. Build, one milestone at a time

For each milestone in the plan:

1. Search for what already exists. For every Action, scope, enum method, model method, component or hook you are about to create, search first, including the underlying conditions (fields, enum cases, query conditions), not only helper names. List the existing owners before writing a new check: `rg -n "function \w+\(" app/Enums app/Models app/Policies app/Actions`. If a rule already exists in the wrong place, move it in this change instead of adding a copy.
2. Place every rule using the owner table in ARCHITECTURE.md, and read the docs/rules/ file for each folder you touch.
3. Copy the structure of the matching file in docs/reference/tasks/.
4. Write the tests first (Pest, docs/rules/tests.md) and run them: they must fail. Then implement in this order: migration, enum, model and factory, policy, action, form request, resource and TypeScript type, controller and routes, React. Add new pages to `mainNavItems` in `resources/js/components/app-sidebar.tsx`.
5. Run `composer check` and fix every failure. Error messages say what to call instead: follow them. For a page, follow DESIGN.md and docs/rules/visual-design.md, and look at it with the Playwright MCP server at desktop and phone width.
6. Commit, then add a timestamped line to Progress with how many tests failed before the code and the `composer check` result. Record any decision you made along the way in the Decision Log.

Before the next milestone, search the code already written in this task for the same condition you are about to write.

## 4. Review

Ask the `reviewer` subagent to review the change against the plan using docs/prompts/review.md. When the change touches pages, keep the app running (`composer dev`) with enough data to show them, so the reviewer can open and screenshot them; `php artisan db:seed` creates the test user it logs in with (test@example.com, password `password`). Fix P0 and P1 findings, run `composer check`, and ask again. Stop after 3 rounds and report what is left. Add a Progress line for each round: `review round <n>: <number> P0, <number> P1`.

If the user pastes review findings, check each one, move the rule to its one owner, update its callers and tests, and verify again.

## 5. Report

In the user's language: each agreed decision and the file and method that own it, what you reused or moved, the `composer check` result, and screenshots for UI changes. Fill in "Outcomes & Retrospective", move the plan to `docs/exec-plans/completed/`, and add anything you deferred to `docs/exec-plans/tech-debt.md`. Remove each tech-debt line you fixed and update that domain's grade and notes in docs/QUALITY.md.

## Parallel work

One plan, one git worktree: `scripts/worktree-up <name>` creates it with its own `.env`, database and port. Do not work on two plans in the same folder.

## Weekly cleanup

Once a week, run docs/prompts/cleanup.md: go through every `[review]` rule, open small fixes, list the gaps in the machine checks and the rules to merge or remove for the user, update docs/QUALITY.md.
