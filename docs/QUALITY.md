# Quality

One row per business domain. The weekly cleanup (docs/prompts/cleanup.md) updates this table.

| Grade | Meaning                                                                                  |
| ----- | ---------------------------------------------------------------------------------------- |
| A     | Follows every golden principle; tests cover every Action, endpoint and button.            |
| B     | One or two small deviations, each listed in docs/exec-plans/tech-debt.md.                 |
| C     | Several deviations or missing tests. Fix before building more in this domain.             |
| D     | Rules written in several places or untested writes. Stop and plan a cleanup.              |

| Domain                          | Grade | Notes                                   | Last checked |
| ------------------------------- | ----- | --------------------------------------- | ------------ |
| Users (starter kit auth and settings) | A     | Rewritten to the v2 rules; every Fortify and passkey endpoint is tested (2.8.2). | 2026-11-01   |
| Notifications                   | A     | Paginated, buttons from can.*, consistency tests (2.8.0, 2.8.2). | 2026-11-01   |
| Public template documentation    | A     | Public template and bilingual guides reviewed. Headless browser checks use isolated sessions and clean temporary screenshots; review found no remaining P0/P1 issues. Source: 245 PHP tests; reference: 293; six frontend tests each. Windows launcher simulated, not verified on Windows. | 2026-11-07 |
| Starter installation | A | 237 PHP tests / 919 assertions and six frontend tests pass; reference 285 / 1076 plus six. MySQL initialization, existing configuration and complex credentials, native installer rewrites and worktree isolation have regression coverage. Actual Installer 5.32.0 agent/human creation and dev startup verified on disposable MySQL; Packagist v1.0.0 and default-main public installations both verified; automatic GitHub sync enabled. | 2026-11-04 |
