# Plans

A plan is the written plan for one change. It lives in `docs/exec-plans/active/<name>.md`, one plan per folder (a second plan goes into a worktree of its own: `scripts/worktree-up <name>`). The workflow tool (`php scripts/workflow.php status`) reads it, records the user's approval and says the next step; when the plan is reported it moves to `docs/exec-plans/completed/`.

Write the plan in the user's language. The headings, the item codes (Q1, D1, S1) and the words `Recommended:`, `Answer:` and `Template:` stay as below: the tool reads them.

## Sections

- **Purpose**: what the user gets, in one or two sentences.
- **Questions**: what only the user can decide. Each is a line `Q1 <question>`, then `Recommended: <your answer, and why>`, then `Answer:`, which you fill in with what the user said. Word a question so "yes" accepts the recommendation. Ask all of them in one message. "None." when there is nothing to ask. The user's 开始 is held back while an `Answer:` is empty.
- **Decisions**: every decision the change follows, each a line `D1 <decision>`: the answers, and what the plan settles itself. A dependency added or upgraded, a check changed and an existing test assertion changed are each a decision of their own. A decision that differs from a rule names the rule. A decision no test can prove says `(no test: <reason>)`; the reviewers check it instead. A plan that syncs a template release has the line `Template: <commit>` here.
- **Screens**: each page the change adds or changes, a line `S1 <page>: <its fields, its actions, its layout>`, in words. For a page the browser tour already visits, `<page>` is its name there (tests/Browser/PagesTest.php: dashboard, log in, ...). Only the screenshot baselines of these pages and of the screens' own tests may change; another page's changed baseline holds back the review. "None." when there is none.
- **Waits for you**: added after the review, before the report: the cases kept as they were because they are the user's to decide, and the dependencies, checks and test assertions the plan did not name. "Nothing." when nothing waits. Writing it does not void the approval.

The user's 开始 approves the plan as it is then. An edit of any other section afterwards voids the approval: the user says 开始 again.

## Tests

Each decision and each screen has a test that names it, and the tool counts what is left:

- a PHP test in the group: `test('...', ...)->group('<plan>.D1')`;
- a JavaScript test whose name starts with it: `it('<plan>.D1 ...', ...)`;
- a screen's browser test in the group `<plan>.S1`; a screenshot it saves for the report is named `<plan>.S1` (`->screenshot(filename: '<plan>.S1')`).

`<plan>` is the plan's file name without `.md`.

## Template

```markdown
# <Name>

## Purpose

<What the user gets.>

## Questions

Q1 <question>
Recommended: <answer, and why>
Answer:

## Decisions

D1 <decision>
D2 <decision> (no test: <reason>)

## Screens

S1 <page>: <fields, actions, layout>
```
