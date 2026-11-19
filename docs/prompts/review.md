# Review prompt

Used by the two reviewer subagents, `reviewer-opus` and `reviewer-haiku` (.claude/agents/). The request names one lane, the round and the two files `php scripts/hooks/finish-check.php round <plan path>` wrote for the round, a copy of the plan and its diff: `Lane: backend. Round: 3. Plan: storage/framework/testing/ezdev-review/<feature>.md. Diff: storage/framework/testing/ezdev-review/<feature>.diff.` In lane `screens` the request to `reviewer-opus` also names the address of the copy of the app: `Lane: screens. Round: 3. App: http://localhost:8101. Plan: … Diff: ….` One run reviews one lane.

---

You did not write this change. Review it with fresh eyes against its plan, for one lane.

You have no shell. Read files with Read, and search them with Grep and Glob.

1. Read the lane, the round, the plan and the diff from the request. If one is missing, or the lane is not one of `backend`, `frontend`, `screens`, stop and end with the result line below with lane `none`.
2. Read the plan: the copy the request names. Its "Agreed decisions" are the specification.
3. Read the plan's diff: the diff file the request names. It holds everything since the commit before the plan was added, new files included; for a plan that syncs the template into a project (`Template: <commit>`), a file that is as it is in that commit is left out and not reviewed. Read the changed files themselves for the code around a change.
4. Read docs/golden-principles.md up to the lane table, the levels included. Your rules are the list items tagged with your lane in .claude/rules/: Grep for `[review:<lane>:` there (`[review:backend:`), and read the files it finds in name order; each rule's tag gives its level; no other lane's rules are yours. A `[PHPStan]`, `[oxlint]` or `[Structure]` rule in those files is checked by machines; if the change breaks one anyway, report it and say which check missed it.
5. For each agreed decision that falls in your lane, find the file and method that implement it.
6. Lane `screens`, `reviewer-opus` only (`reviewer-haiku` has no browser and skips this step): if the change touches resources/js, resources/css, resources/views, lang, DESIGN.md or docs/design, look at the screens. The builder ran `scripts/screen-round`, which made a copy of the app at the commit under review, with a database that holds only the test accounts of database/seeders/DatabaseSeeder.php (password `password`). With your isolated headless Playwright MCP server (`playwright`), open the address the request names (`App: <url>`). First walk every item of the plan's walk list (under Validation: `W1 <role> (<test account>): <flow>`) through the app's forms, from its first step to its last, signed in as the item's test account; a step that fails (an error page, a refusal the plan does not give, a missing button) is a level 1 finding. A step the item says a browser cannot do names the test that covers it: check that the test exists and asserts that step, and walk on. Report one line per item: `W1 walked`, or `W1 not walked: <reason>` for an item you did not try. An item walked up to a step that failed is walked, and the failed step is the finding. A plan without a walk list has nothing to walk. The pages to look at show the data that walk made: never add rows any other way. Then, with the data the walk made, open every page the change adds or changes. Take temporary screenshots at 1280×800 and at 390×844, and one in dark mode (Settings, Appearance), without specifying filenames. Also open the style overview page (`/style`, development only) and compare each changed page with the pattern demo and the component demos there: a page that looks different from its pattern's demo, with no reason in the plan, is a finding. Also open each preview the plan's agreed decision names (`storage/app/private/design-previews/<plan file name>/`, with Read): a page that differs from its accepted preview, with no decision that says so, is an agreed decision implemented differently. Check the rules of the lane before closing the browser, and set Appearance and Language in Settings back to what they were when you logged in; `browser_close` removes those temporary files. Close the browser even on error; the launcher also closes it after tool errors. Every file the browser tools save, also under a file name or a path you give them, stays in the browser's temporary folder (scripts/playwright-mcp) and is removed when the browser closes: nothing you save reaches the project folder. If the user explicitly asks to keep a screenshot, say so in your report and the builder takes it. If the app does not answer or a page cannot be reached, report `[level 1] app not running: <page> not checked` instead of guessing.
7. List every rule of your lane that the change touches (it adds or changes code the rule is about), file by file in name order and in the order each file lists them, each with `ok` or the finding. Leave out the rules it does not touch.
8. Report as findings only a clear break of something written, grouped by level (level 1 first), each as `[level <n>] path:line`, what is wrong, and the rule, the decision or the walk item it breaks:
    - a rule of your lane broken in the changed code: the level of the rule's tag;
    - an agreed decision implemented differently: level 1;
    - a step of the walk list that fails: level 1.
    A finding that names none of these is not a finding. Every finding is fixed, whatever its level (docs/golden-principles.md, Levels).
9. Anything else you notice is not a finding and is reported in its own list, one line each:
    - "Rule gaps": in the plan's changed code, a case that no written rule settles, behaviour the plan does not have included.
    - "Decisions that differ from a rule": what the plan decided explicitly where it differs from a written rule.
    - "Older code": a problem in code from before the plan. The builder adds it to docs/exec-plans/tech-debt.md.
10. Then one line on the plan: which decisions of your lane are implemented and which are missing.
11. Skip style and naming. A copied block of code and hand-written formatting are not style: they break rules of your lane. Do not suggest new abstractions. Do not edit files.

End with exactly one result line, the last line of your answer, with the counts of the findings above (0 when there are none), and the round as the request names it:

`RESULT lane=<lane> round=<n> L1=<count> L2=<count> L3=<count>`

In lane `screens`, `reviewer-opus` ends the line with the number of items it did not walk: `RESULT lane=screens round=3 L1=0 L2=0 L3=0 unwalked=0`. A run with an item not walked, or with an item of the plan's walk list that has no `walked` line in the report, counts as not run. A run with no result line counts as not run.
