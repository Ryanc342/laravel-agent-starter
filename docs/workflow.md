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
5. Identify affected behavior and callers, including unchanged dependent tests. Run `composer check:milestone -- <test files or directories>`: full Pint, PHPStan, frontend formatting/lint and TypeScript checks, followed by the selected PHP and/or frontend tests. Select existing paths under tests/Unit, tests/Feature, tests/Structure or tests/js; runner options are not accepted. For example: `composer check:milestone -- tests/Feature/Notifications tests/js/lib/format.test.ts`. Run relevant structure tests when changing rules or tooling. For a page, follow DESIGN.md, the docs/design/ files it names and docs/rules/visual-design.md. Its page check with Playwright is the final review's `look` and `behaviour` rounds (step 4): make no browser pass of your own before them. Fix every failure.
6. Commit, then add a timestamped line to Progress with how many tests failed before the code, the affected scope, the exact command and its result. Call this a milestone/affected-test pass, never a full-suite pass. Record any decision you made along the way in the Decision Log.

A finding that needs the user's decision (two rules that disagree, a default the plan does not give, a deviation from a pattern) goes to the user in the same round, in the grilling format of step 1. When every review step left is the user's answer to such findings, or a reviewer run of the conversation is still going, the finish check lets you stop without a send-back and tells the user what the session waits for; what you write yourself never releases a stop. Write the answer in the plan's Decision Log as a line that starts with `Accepted:` and quotes the user's answer. Only the user's answer makes such a line: never write one yourself. What an earlier plan left for the user is marked the same way in docs/exec-plans/tech-debt.md. The reviewers read both and do not report what an `Accepted:` line covers.

Run full `composer check` earlier when changing shared test/database infrastructure or when the affected scope cannot be bounded reliably. For documentation-only work with no affected tests, `composer check:static` plus relevant structure tests is enough during the milestone; do not invent a test just to supply a path. The plan names the selection and why it covers the impact.

When all implementation milestones are finished, run full `composer check` before review and delivery. No test suite is removed from that command or from CI. `composer check:static` alone is not a milestone pass for code changes. The finish check accepts a fresh milestone result only while a valid active plan has both completed and remaining milestones; completed or archived plans and changes without such a plan still require a full pass on the current code.

Full and milestone checks share one lock per worktree, retained until the runner and its live descendants exit. Killing a parent does not make its still-running children safe to overlap; retry after they exit. A second overlapping command reports that a check is already running; it cannot replace that run's state. A new attempt revokes prior passes, and only the same attempt completing successfully can record a new pass, including its own end-of-suite proof. Interrupted runs and legacy fingerprint-only records need a new check. Use the Composer entry points, not the internal `checking` / `checked` hook modes.

Final acceptance follows the plan across conversations: ticking every milestone or newly archiving a plan needs a current full pass even if this conversation only changes Markdown. A refused archive stays pending across conversations until validated; text in Progress cannot establish a pass. Ordinary documents, drafts, intermediate progress and historical completed-plan record corrections do not by themselves start a new code acceptance.

Before the next milestone, search the code already written in this task for the same condition you are about to write.

## 4. Review

No milestone has a review round, whatever the number of milestones: every lane is reviewed here, in the final review. A `(review)` mark on a milestone line asks for nothing. Run full `composer check` first. Then the final rounds, numbered 1, 2, 3 for the plan. A round is both reviewers at the same time: for each lane the plan's changes touch since the plan was added that is not done yet, one run of `reviewer-opus` and one of `reviewer-haiku`, started together on the same commit, with the same round number in the request: `Lane: <lane>. Round: <n>. Plan: <plan path>.` (docs/prompts/review.md). Commit the code before each round: a round of uncommitted code does not count. A lane is done when, in its latest round, both reviewers report no P0 and no P1, the code was committed when they ran, and no file of the lane's places has changed since; a later round replaces an earlier one. A later commit that changes only docs/ or *.md (Progress, the move to completed/) keeps the round. A code change voids only the lanes its files touch (the lane table) and keeps the others: a lane done earlier needs a new round only when a later fix touches it, and a fix that breaks a rule of another lane is left to the weekly cleanup.

A round runs to its end before the code changes. Start the runs of a round together. While one of them is still going, change no code (any file outside docs/ that is not Markdown) and stop no reviewer. When every run of the round has reported, fix the P0 and P1 findings of the whole round, put the findings marked for the user to the user, commit, and start the next round for the lanes that are not done. Stop a reviewer run only when it has reported nothing 30 minutes after its start, then start it again with the same request. The finish check drops the start record of a run you stop, and after 30 minutes it no longer counts a run that never stopped as still going.

The screens come last. The lanes `look` and `behaviour` start only when every other lane the plan touches is done. These rounds are the plan's page check with Playwright: make no browser pass of your own before them. When a screen finding leads to a code fix, the lanes its files touch are reviewed again, the other lanes first and the screens after them.

A screen round runs on two seats at the same time. `reviewer-opus` reviews the project's own app with the `playwright` browser; `reviewer-haiku` reviews the review copy with the `playwright-review` browser, a second isolated headless browser of its own. The review copy is one git worktree next to the project (`../<project folder>-review`), detached at the commit under review, with its own .env: its own database (`<database>_review`, or its own SQLite file), its own port (the project's port plus 100) and its own session cookie. Nobody writes code in it, and nothing deletes it automatically. Keep the project's app running (`composer dev`). Before each screen round run `scripts/screen-round`, without asking the user (Claude Code allows it):

- It creates the review copy the first time and reuses it afterwards, puts the commit under review into it (dependencies installed when a lock file changed, assets built), resets both databases to the seeded state, which holds only the test accounts (test@example.com, password `password`), starts or restarts the review copy's app, makes sure both apps answer, and prints the two addresses.
- It makes the project's own app show the current frontend: when a Vite dev server named in public/hot answers, nothing is built; without public/hot it runs `npm run build` in the project; a public/hot whose server does not answer is reported like an app that is down.
- When the project's own app does not answer, it says so: restart `composer dev` and run it again. No screen run starts before it has passed.
- It resets the project's database only when APP_ENV is `local` and the database is on this machine (SQLite, or DB_HOST `127.0.0.1`, `localhost` or `::1`); otherwise it changes nothing, and you ask the user. The review copy's database is always reset. No other database is ever reset without asking. Rows typed into the development database by hand are lost at each screen round.

A change to the reference feature (docs/reference/tasks/) is reviewed too: its files other than Markdown are code, and each touches the lanes its path inside the feature would touch (docs/reference/tasks/app/ as app/, and so on). The project's own app does not have the feature, so when the plan's changes touch it the screen rounds run `scripts/screen-round --reference`: seat 1 is a second copy (`../<project folder>-reference`, its own database, port, the project's plus 200, and session cookie) instead of the project's own app, both copies get the feature installed the way `composer check:reference` installs it, both databases are reset, and it prints the two addresses. The project's own app and database are not touched in that mode, and a later round without `--reference` gives the review copy the commit's files only again.

Then start both seats at once. Inside a seat `behaviour` runs first and `look` after it, because the walk makes the data the pages show. Each request names its seat's address: `Lane: <lane>. Round: <n>. App: <url>. Plan: <plan path>.` The `behaviour` reviewer walks every item of the plan's walk list (docs/PLANS.md, under Validation: `W1 <role>: <flow>`) through the forms, from its first step to its last, as that role, and reports one line per item, `W1 walked` or `W1 not walked: <reason>`; a step that fails is P0. Both seats walk the same list. A run with one or more items not walked counts as not run: fix what it reported, and that seat walks again in the same round, after `scripts/screen-round`, once the other seat's runs have reported and before its own `look` run. That walk makes the data for looking at the pages: factories are for tests, and no row in the app's own database is made by one (docs/rules/tests.md).

Fix P0 and P1 findings between rounds and verify the affected scope. Before final acceptance, any code changed after the full pass must pass full `composer check` again; a milestone pass cannot replace it. A lane is reviewed until it is done: there is no cap on rounds, and rounds are counted per lane, because the screen lanes start later than the others. After every fourth round of a lane that is still not done (its 4th, 8th, 12th and so on), write one line to the user in the conversation and the same line in Progress: the lane, how many rounds it had, and what is still open; then go on without stopping. The finish check's send-back names how many rounds each open lane has had. Add a Progress line per run: `review round <n>: <lane>, <reviewer>, <number> P0, <number> P1`. Every run also writes a line to the review ledger (scripts/hooks/finish-check.php `reviewed`, from Claude Code's SubagentStop hook), and the finish check reads the ledger, not Progress: a run without the result line of docs/prompts/review.md counts as not run.

If the user pastes review findings, check each one, move the rule to its one owner, update its callers and tests, and verify again.

## 5. Report

In the user's language: each agreed decision and the file and method that own it, what you reused or moved, the `composer check` result, and the UI inspection results. Include screenshots only if the user explicitly asked to keep them. Fill in "Outcomes & Retrospective" and add anything you deferred to `docs/exec-plans/tech-debt.md`. Remove each tech-debt line you fixed and update that domain's grade and notes in docs/QUALITY.md. Move the plan to `docs/exec-plans/completed/` last, only when every lane it touches is done: first run `php scripts/hooks/finish-check.php review <plan path>`, which reads the review ledger and prints each lane as done or as not done with the reason, and changes no file; move the plan only when it prints every lane as done. The finish check asks for the move only then, and a plan moved earlier stays unfinished for it. Each ledger line names the plan its request names, so a round run after the move still counts for the plan.

## Parallel work

One plan, one git worktree: `scripts/worktree-up <name>` creates it with its own `.env`, database and port. Do not work on two plans in the same folder.

## Cleanup

Once a week, run docs/prompts/cleanup.md in a new conversation for the whole repository. A completed plan has no cleanup of its own: its review rounds checked its lanes, and the weekly cleanup finds what a fix broke in another lane. The cleanup goes through every `[review]` rule, lane by lane, opens small fixes, lists the gaps in the 18 machine checks and the rules to merge or remove for the user, and updates docs/QUALITY.md.
