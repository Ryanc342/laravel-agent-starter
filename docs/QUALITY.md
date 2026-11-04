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
| Public template documentation    | A     | Bilingual setup, MIT notices, contribution and security guides reviewed; isolated setup and checks pass (224 tests; reference 272). GitHub publication awaits the recorded CI PHP correction approval. | 2026-11-04 |
