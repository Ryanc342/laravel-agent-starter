---
paths:
  - "tests/**"
---

# TEST: Tests

Location: `tests/ (part: tests/Feature/PartRoutesTest.php calls every route a part opens)`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Does

- **TEST-1** Proves the behaviour still works. `note`

## Must

- **TEST-2** Each decision of the plan has a test that names it: a PHP test in the group <plan>.D1, a JavaScript test whose name holds <plan>.D1; each screen (S1) has a browser test named the same way. A decision no test can prove says "(no test: <reason>)", and the reviewers check it. `machine` (The workflow tool: the step built (php scripts/workflow.php status))
- **TEST-3** A test that names a decision or a screen proves it: the test fails when the code does not do what the plan says there. `reviewer`
  - Check: Look at: tests whose group or name holds <plan>.D<n> or <plan>.S<n>, and that decision or screen in the plan. Break: a test that would still pass if the code did not do what the plan says (no assertion, only a status or a page load, an assertion on something else); a screen test that does not check the fields and actions the plan lists for the screen. Not: one test for a part of a decision when other tests with the same name cover the rest. Cite: the test and the decision.

## Also applies here

- **ACT-16** (from ACT) The operation part's own test proves the lock holds (tests/Feature/Parts/OperationLockTest.php); an operation writes no lock test of its own. `part`

## Arrows

- FACT>MODEL: Factories → tests and seeders ([card](./fact-to-model.md))
