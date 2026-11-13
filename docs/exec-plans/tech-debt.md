# Tech debt

Known debt, oldest first. Add a line when you defer something. Fixing a line is a request like any other (docs/workflow.md): grill, plan, wait for "start". When it is fixed, remove the line and update that domain's grade and notes in docs/QUALITY.md.

| Added      | Where | What | Why deferred |
| ---------- | ----- | ---- | ------------ |
| 2026-11-13 | resources/js/pages/welcome/index.tsx | The home page still uses raw colours and sizes that `ui` rules forbid elsewhere; it is rebuilt with the landing patterns. | The plan keeps `/` as it is until batch 3 (decision 39). Do not copy from it (docs/rules/visual-design.md). |
| 2026-11-13 | resources/js/hooks/use-validation-error-toasts.ts | After a failed submit the toast works for every error key, but the field to scroll to and focus is found by `name` equal to the error key. An input named `items[0][name]` for the error key `items.0.name` is not found, so nothing is scrolled or focused. | No form here has array or dotted names; the first feature with line items needs a lookup for them (and a test). |
| 2026-11-13 | resources/js/components/shared/column-menu.tsx, resources/js/components/style/list-demo.tsx | The "Amount" range takes the numbers the user types as they are; a real amount column must decide in which unit the user types (currency or cents) and convert in the backend. The demo's range does not filter. | The only amount column today is the style page's example, and the reference tasks have no amount. |
