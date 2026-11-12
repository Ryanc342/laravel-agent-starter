# Tech debt

Known debt, oldest first. Add a line when you defer something. Fixing a line is a request like any other (docs/workflow.md): grill, plan, wait for "start". When it is fixed, remove the line and update that domain's grade and notes in docs/QUALITY.md.

| Added      | Where | What | Why deferred |
| ---------- | ----- | ---- | ------------ |
| 2026-11-13 | resources/js/pages/welcome/index.tsx | The home page still uses raw colours and sizes that `ui` rules forbid elsewhere; it is rebuilt with the landing patterns. | The plan keeps `/` as it is until batch 3 (decision 39). Do not copy from it (docs/rules/visual-design.md). |
| 2026-11-13 | resources/js/components/ui/alert.tsx, resources/js/components/ui/placeholder-pattern.tsx, tests/Structure/FoldersTest.php | `alert.tsx` and `placeholder-pattern.tsx` are unused; the starter kit list in `FoldersTest.php` still names the deleted `alert-error.tsx`. Delete the two files and the list entry. | Removing a shadcn file and editing a protected test were outside batch 1's milestones. |
