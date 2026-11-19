# Template simplification (1.4.0)

## Purpose
Ryan answers questions only while a plan is written. After his "开始" the session asks nothing and does not flood the screen with send-backs, and project quality stays where it is. The template gets smaller: most changes remove text, rules and hook machinery.

## Agreed decisions
Agreed with Ryan in the planning chat on 2026-11-19. The group names are for reading only.

Questions
1. One rule replaces every execution-time question: after "开始", whatever the plan does not say follows the template rules or keeps the current behaviour, and goes into the final report. Remove every sentence that sends a question to the user during execution, among them: AGENTS.md "Anything else is a new question for the user" and the whole "When stuck" section; docs/workflow.md "Decisions are the user's ... Never answer your own decision questions" (for execution), the paragraph on findings that need the user's decision, and "you ask the user" in the screen-round part; docs/PLANS.md "Anything new goes back to the user as a question"; golden-principles "a new top-level folder is a decision for the user"; docs/rules/frontend.md "if a page is measured slow, ask the user"; docs/design/components.md "ask the user first if it brings a new package"; DESIGN.md and docs/rules/visual-design.md "Tell the user, who adds its pattern later". Also remove "Record any decision you made along the way in the Decision Log" (workflow.md step 3) and "decision taken during the work" (PLANS.md template): during execution the AI makes no decisions, it follows the rules or keeps the current behaviour.
2. Money, permissions, who can see which data, and deleting or changing real data: when such a case comes up during execution, keep the current behaviour and list it in the report for Ryan to decide.
3. Plan-time questions: ask only what the template rules and the code cannot answer; when nothing is open, hand in the plan directly. This replaces "Every request starts with questions", "Interview the user relentlessly", "There is no cap on the number of questions" and the `[user]` rules of principle 9.
4. While writing the plan, the AI says which decision differs from which template rule.
5. The walk list holds only steps a browser can do; a step it cannot do names the test that covers it.
6. The pop-up question tool is gone: `"AskUserQuestion"` in permissions.deny of .claude/settings.json.
7. Workflows are off: `"Workflow"` in permissions.deny. Reviews are always the two reviewer subagents (runs of a workflow were not written to the review ledger).

Approval
8. "开始" is the approval. A UserPromptSubmit hook snapshots the active plan when Ryan's message, with spaces and punctuation removed and lower-cased, is one of `开始`, `开始吧`, `好开始`, `好开始吧`, `好的开始`, `ok开始`, `start`. The snapshot is a copy of the plan file and the commit it was taken at, kept in the state folder (.git/ezdev/approved/<plan file name>). Later checks and reviews compare against the snapshot; what the AI changes in the plan afterwards does not count.
9. A new "开始" after a change of requirements takes a new snapshot.
10. Every plan has a section "Approved changes": the protected files it changes, the dependencies it adds or upgrades, and the existing tests whose assertions change, each with the decision that changes the requirement. A protected change that is not in the snapshot's list is reverted, without asking. At plan time the AI looks up the protected docs a change will affect and lists them; one it missed is not changed and goes into the report.
11. The protected list names only the template's own files, path by path (no project helper in tests/Support/ or scripts/ is protected). composer.json and package.json are protected only in their dependency sections.
12. Remove "approve each file again in every conversation": the approval lives in the snapshot.

Reviewers
13. Reviewers report two kinds of findings only: a clear break of a written rule, and behaviour the plan does not have. The AI fixes both, always.
14. A case no rule settles is not a finding: it goes into the report's "Rule gaps".
15. Reviewers do not report what the plan decided explicitly; a decision that differs from a rule is listed in the report as "decision differs from rule".
16. Remove "(for the user)" and `user=` from the result line, the prompts and the finish check.
17. Reviewer Bash allows every command that only reads (search, viewing files, git commands that read); writes, redirections, substitutions and network stay refused. (Earlier counts: 531 refusals in total, 393 in one day in DulaBot.)
18. When the template is synced into a project, files identical to the template's are not reviewed.
19. If M1's probe passes, reviewers run in the foreground (`CLAUDE_CODE_DISABLE_BACKGROUND_TASKS=1` in the env of .claude/settings.json), and these are removed: the SubagentStart `reviewing`, PostToolUse SubagentHandback `handback` and TaskStop `stopped` hooks, the 30-minute rule, "a round runs to its end before the code changes", and the finish check's release while a reviewer runs. If the probe fails, all of it stays.
20. golden-principles.md lists each `[review]` rule once, under its lane. A reviewer reads docs/prompts/review.md and its own lane's section only.
21. Remove the review rule that counts AGENTS.md lines and checks every named path, every plan's headings and the rule format (principle 7, last rule): the finish check and the cleanup already do this.
22. The walk list names each role's test account, and database/seeders/DatabaseSeeder.php creates one test account per role. Remove "the account the plan's Progress names for a role" and the reviewer's own walk list for a plan without one.

Finish check
23. Four checks only: changed code has a plan; changed code passed the checks on this code (a milestone pass while milestones remain, a full pass at the end); a finished plan has every touched lane done; a protected change is in the snapshot.
24. Remove the two exits that stop to ask the user (asking about protected files, waiting for answers to findings), the tech-debt to QUALITY check, and the completed plan's Progress word checks ("composer check", "review").
25. One line per reminder. Each reminder at most 3 times per conversation; its count is not reset by a code change (the key holds no fingerprint or reason hash). At most one message to Ryan per conversation.
26. Only a plan moved from active/ to completed/ in this conversation counts as finished in it; plans brought in by a sync are history.
27. SessionStart says nothing to the user. In a new conversation "继续" continues the work without first reporting open steps.

Screens and tests
28. Tests never use the Vite dev server: SSR is off in the test environment (phpunit.xml).
29. Both screen-round seats are copies, each with its own database. The project's own app and its development database are never touched, and `composer dev` is not needed.
30. scripts/screen-round picks free ports.

Plan format and report
31. Progress: one line per milestone or review round, with the date and the commit, no time of day. A review round is one summary line; its details stay in the ledger.
32. Remove the requirements to record affected scope, test paths, commands and results per milestone, and to run an early full check for "shared infrastructure or unbounded impact": docs/workflow.md step 3 items 5 and 6 and the paragraphs after them, docs/PLANS.md (Rules and Validation), golden principle 9, docs/rules/tests.md "Validation scope", docs/prompts/cleanup.md step 4, docs/prompts/upgrade.md C step 3, README.md and README.zh-CN.md, the description of check:milestone and the finish check's send-back text. The final full check stays.
33. The report speaks business language: what was built, the check results, the rule gaps, what waits for Ryan. No file or method names.

Removed exceptions
34. Remove `skipUnlessFortifyHas` from tests/TestCase.php. Turning a Fortify feature off deletes its tests, approved with the plan (decision 10).
35. The weekly cleanup changes no protected file; doc fixes go into its report.
36. The template ships docs/QUALITY.md with one short row per real domain (Users, Notifications) and an empty table in docs/exec-plans/tech-debt.md; the template's own history moves to CHANGELOG.md. With this, a sync needs no rule to keep the project's own rows.
37. docs/prompts/upgrade.md: every kind of update runs the normal final review, screens included. Remove part A's path without review and part C's own screenshots.

Text
38. One rule for changing or deleting a test assertion: only when a decision of the plan changes the requirement, naming that decision (AGENTS.md red line and Code Review Rules, docs/rules/tests.md, golden-principles).
39. Remove "welcome is Laravel's own page ... do not copy from it" from docs/rules/visual-design.md.
40. One statement of where shared traits go (ARCHITECTURE.md "validation rule traits" vs golden-principles principle 3, enums).
41. No doc states how many rules or checks there are.
42. Every rule and every step is written in one place; other places point to it. In particular: the workflow (AGENTS.md, docs/workflow.md, docs/PLANS.md, golden-principles, both READMEs); the protected-files statement (AGENTS.md, visual-design.md, docs/reference/tasks/README.md, tests.md); AGENTS.md "Code Review Rules" becomes a pointer; ARCHITECTURE.md's check table becomes a pointer; DESIGN.md Do's and Don'ts vs the `ui` rules; the reviewer commands (review.md vs the script); the two reviewer agent files hold only their model, their browser and "follow docs/prompts/review.md". Inside golden-principles merge the duplicates: `noValidate` (frontend and behaviour), DatePicker (two rules), StatTiles (two rules), the confirmation dialog (three rules), the field error (three rules), the icon-only button's tooltip and label (three rules), and "fix the code, not the test" (red line).
43. docs/workflow.md says only what the AI does. Tooling internals (check locks and attempts, final acceptance across conversations, screen-round internals) move to the scripts' own comments. Remove dead text: the `(review)` milestone mark (workflow.md, PLANS.md, golden-principles) and review.md's "or pasted into a new conversation".
44. Every doc that describes the old approval or "ask the user" (READMEs, upgrade, cleanup) describes the new flow.
45. README.md and README.zh-CN.md say that Claude Code asks once to trust the project on first open, and that this is needed.
46. docs/design/patterns/pricing.md and golden-principles agree on the page title of the pricing page (the display size belongs to PricingPlans; golden says the page title is the largest text).

Skills
47. boost.json keeps only `wayfinder-development`; remove the other six skill folders from .claude/skills/. Before removing, port into docs/rules what they hold and the template lacks (for example eager loading against N+1, HTTP client timeouts and retries, deployed migrations not edited, upload validation), without contradicting a template rule. Remove docs/rules/controllers.md's sentence about the laravel-best-practices skill.

Release
48. Released as 1.4.0 after the final review. Publication waits for Ryan's word "发布", as for 1.3.0: fast-forward `main`, push, CI green, then the tag.

## Approved changes
- Protected files: AGENTS.md, ARCHITECTURE.md, DESIGN.md, docs/golden-principles.md, docs/rules/, docs/design/ (components.md, foundations.md and the pattern files that repeat a rule), docs/prompts/, docs/workflow.md, docs/PLANS.md, docs/reference/tasks/README.md, phpunit.xml, tests/TestCase.php, tests/Unit/Hooks/, tests/Structure/ (only where a check names a removed file or a count), scripts/hooks/, scripts/screen-round, scripts/screen-round.php, scripts/check-milestone.php, .claude/settings.json, .claude/agents/, .claude/skills/, .github/ (only if CI names a removed file).
- Dependencies: none added or upgraded.
- Existing test assertions that change: tests/Unit/Hooks (decisions 8 to 27, the hook behaviour changes); any test of `skipUnlessFortifyHas` (decision 34); tests/Structure only for decisions 41 to 43.
- Not protected, also changed: boost.json, docs/QUALITY.md, docs/exec-plans/tech-debt.md, README.md, README.zh-CN.md, CHANGELOG.md, database/seeders/DatabaseSeeder.php.

## Milestones
- [x] M1 Probe, no template change (decision 19): in a scratch worktree with `CLAUDE_CODE_DISABLE_BACKGROUND_TASKS=1`, check that (a) three subagents started in one message all return before the session goes on, and (b) a full `composer check` finishes as a foreground command. Record both in Surprises & Discoveries.
- [x] M2 Snapshot and finish check (decisions 6 to 12, 16, 19 as M1 found, 23 to 27). Tests first in tests/Unit/Hooks. Ryan's "开始" for this plan came before the hook existed: M2 ends by recording, once, the snapshot of this plan as committed when he approved it, with a command that is not kept in the template.
- [x] M3 Questions and approval in the docs (decisions 1 to 5, 8 to 12).
- [x] M4 Reviewers (decisions 13 to 22).
- [x] M5 Screens and tests (decisions 28 to 30).
- [x] M6 Plan format and report (decisions 31 to 33).
- [ ] M7 Exceptions and text (decisions 34 to 46).
- [ ] M8 Skills (decision 47).
- [ ] M9 Final: full `composer check` and `composer check:reference`, the final review of every touched lane; a trial in a scratch worktree from a small request to the report (questions only before "开始", none after, no send-back flood); CHANGELOG.md 1.4.0 and both READMEs; then stop for "发布" (decision 48).

## Validation
- Per milestone: `composer check:milestone -- <affected test paths>` passes.
- Final: full `composer check` and `composer check:reference` pass on the final code.
- tests/Unit/Hooks prove the hook behaviour: a snapshot on each "开始" variant and on no other message; a protected change outside the snapshot is reported; only the four checks; a reminder at most 3 times without reset; at most one message; synced plans ignored; reviewer read-only commands allowed and writes refused.
- No page changes, so no walk list.

## Progress
- 2026-11-19 M1 probe passed (a and b), no template change, commit 6d0ff09e.
- 2026-11-19 M2 snapshot and finish check: 44 hook tests failed before the code, then `composer check:milestone -- tests/Unit/Hooks` passed (318 tests); the one-time snapshot of this plan is still missing (Surprises), commit 64b624ef.
- 2026-11-19 M3 questions and approval in the docs; AGENTS.md, docs/workflow.md and docs/PLANS.md are written in their final form, so they also carry decisions 13, 19, 22, 27, 31 to 33, 42 and 43 for those files; `composer check:milestone -- tests/Structure` passed, commit 4a807f71.
- 2026-11-19 M4 reviewers: 15 hook tests failed before the code (14 command checks, 1 template sync); golden-principles by lane, the review prompt, the agent files, the seeder; `composer check:milestone -- tests/Unit/Hooks tests/Structure` passed (368 tests), commit 2ffecea8.
- 2026-11-19 M5 screens and tests: 1 test of the Vite dev server and 9 screen-round tests failed before the code; SSR off in phpunit.xml, both seats are copies with their own database and a free port; `composer check:milestone -- tests/Unit/Hooks tests/Feature/ViteDevServerTest.php tests/Structure` passed (369 tests), commit a4058421.
- 2026-11-19 M6 plan format and report: no scope records and no early full check in docs/rules/tests.md, the cleanup and upgrade prompts and both READMEs; the READMEs' flow is written in its final form, so it also carries decisions 44 and 45; `composer check:milestone -- tests/Structure` passed, commit M6.

## Surprises & Discoveries
- M1 (a): with `CLAUDE_CODE_DISABLE_BACKGROUND_TASKS=1` in the env of .claude/settings.json, three subagents started in one message ran together and all three tool results held their answers before the session went on. Without it, each tool result said "Async agent launched" and the answers came later as notifications.
- M1 (b): a full `composer check` ran as one foreground Bash call in 236 s (679 Pest tests, 208 s) and ended with exit 0. It needs a Bash timeout above the default two minutes; the call passed 600000 ms, the maximum.
- M1, extra: in the foreground a reviewer's SubagentStop input carries the whole report, result line included, in `last_assistant_message`; the run used no hand-back tool. So the ledger needs no `handback` hook.
- M2: a reviewer that runs in the background hands its report back with the SubagentHandback tool. This conversation started before the env setting existed, so its reviewers still run in the background; `reviewed` reads the handed-back report from the subagent's transcript when the last message has no result line. No hook is needed for that.
- M2: the auto-mode classifier refused the AI's command that would have written this plan's snapshot (`.git/ezdev/approved/template-simplification.md`, from commit 6d0ff09e) as the AI approving its own plan. It was not worked around. Until Ryan records it, the finish check reads no "Approved changes" for this plan and asks to revert every protected change of this branch.

## Decision Log
- 2026-11-19 Ryan said "开始" to this plan in the planning chat. The current finish check reads approvals only from the conversation that changes the files, so the implementing conversation adds the approval line for the protected files in "Approved changes" after Ryan's "开始" there.
- 2026-11-19 Ryan said "开始" in the implementing conversation: it approves the protected changes listed in "Approved changes". The work runs on the branch `template-simplification`; `main` moves only at "发布" (decision 48).

## Outcomes & Retrospective
