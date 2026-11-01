# Tech debt

Known debt, oldest first. Add a line when you defer something; remove it when it is fixed.

| Added      | Where | What | Why deferred |
| ---------- | ----- | ---- | ------------ |
| 2026-11-01 | tests/Structure/ResourceTypesTest.php | Resource fields and TypeScript types are compared by a test that reads both files as text. Replace it with the TypeScript types Laravel Wayfinder generates for page props, then delete the test and the "Matches ..." comments. | Wayfinder's generated page prop types are still in beta. |
