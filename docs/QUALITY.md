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
| Notifications                   | A     | Paginated, buttons from can.*, consistency tests (2.8.0, 2.8.2). The dashboard block loads the newest five like the bell; the row's drawing is the shared `FeedRow` (frontend design v2, batch 1). | 2026-11-13   |
| Public template documentation    | A     | Public template and bilingual guides reviewed. Headless browser checks use isolated sessions and clean temporary screenshots; review found no remaining P0/P1 issues. Source: 245 PHP tests; reference: 293; six frontend tests each. Windows launcher simulated, not verified on Windows. | 2026-11-07 |
| Starter installation | A | 237 PHP tests / 919 assertions and six frontend tests pass; reference 285 / 1076 plus six. MySQL initialization, existing configuration and complex credentials, native installer rewrites and worktree isolation have regression coverage. Actual Installer 5.32.0 agent/human creation and dev startup verified on disposable MySQL; Packagist v1.0.0 and default-main public installations both verified; automatic GitHub sync enabled. | 2026-11-04 |
| Validation workflow | A | Retries revoke full/partial passes; one run identity and a worktree lock cover live descendants and suite completion. Final plan acceptance survives conversation boundaries, including refused archives. Source: 307 PHP tests / 1161 assertions plus six frontend tests; reference: 355 / 1318 plus six. Auction isolated compatibility: 131 / 368. Review round 2: no P0/P1. Release aeae7b8 passed Linux CI and both real Installer paths; Packagist v1.1.1 source/dist verified. No UI changed. | 2026-11-10 |
| Frontend design (v2, batch 1) | B | A four-layer design spec (DESIGN.md, `docs/design/`), the clean and bright theme, shared blocks, nine page patterns with demos on `/style` (development only), one error page, a dashboard with real data. Every changed page was looked at at 1280px, 390px and in dark mode; none overflows sideways. Source: 327 PHP tests / 1302 assertions plus 39 frontend tests; reference: 375 / 1459 plus 39. Two deviations in docs/exec-plans/tech-debt.md: the home page keeps its old styling until batch 3, and two unused shadcn files plus a stale list entry remain. Not looked at: every section of `/style` in dark mode. | 2026-11-13 |
