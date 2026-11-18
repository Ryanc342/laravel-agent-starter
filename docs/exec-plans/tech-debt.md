# Tech debt

Known debt, oldest first. Add a line when you defer something. Fixing a line is a request like any other (docs/workflow.md): grill, plan, wait for "start". When it is fixed, remove the line and update that domain's grade and notes in docs/QUALITY.md.

| Added      | Where | What | Why deferred |
| ---------- | ----- | ---- | ------------ |
| 2026-11-17 | docs/exec-plans/ (Progress) | The times in a plan's Progress were written ahead of the clock in three plans and corrected from the commit times afterwards. Nothing compares a Progress time with the clock or the commits. | Found before the 1.3.0 release; the user chose to leave it for later. |
| 2026-11-18 | resources/js/components/style/approval-demo.tsx | After a refused rejection reason, focus stays on the button instead of moving to the reason field (principle 10, focus the first field with an error). The row menu demo had the same gap and was fixed in 1.3.0. | Found in a 1.3.0 screen round; the approval demo was outside that change. |
