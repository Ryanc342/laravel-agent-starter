# Tech debt

Known debt, oldest first. Add a line when you defer something. Fixing a line is a request like any other (docs/workflow.md): grill, plan, wait for "start". When it is fixed, remove the line and update that domain's grade and notes in docs/QUALITY.md.

| Added      | Where | What | Why deferred |
| ---------- | ----- | ---- | ------------ |
| 2026-11-15 | lint/oxlint/ezdev.js (`iconButtonLabel`, `noDateInput`) | `ezdev/icon-button-label` checks a `Button` only when `size` is a string literal starting with `icon`: `size={variable}` or an icon-only button with no `size` is not caught. `ezdev/no-date-input` reports only the literal `type="date"`: `type={'date'}` or `type={variable}` is not caught. | Found in the release check of 1.2.0; the user chose to release first and fix in a later plan (lint/ is protected). |
| 2026-11-15 | lint/oxlint/ezdev.js (`no-untranslated-text`) | The rule does not read `toast()` arguments, literals inside conditions or object values, so an untranslated sentence there passes (the reviewer found nine in `/style`). | Recorded in QUALITY.md by batch 3 feedback 2; listed here at the 1.2.0 release so it has an owner. |
| 2026-11-15 | resources/js/** | `t()` called at module level (before the locale is known) shows only as a hydration warning in the browser; no lint rule or test catches it. | Same as above. |
| 2026-11-15 | docs/reference/tasks/ pages, the print page at 390px, the two-factor dialog | Never opened in a browser: the reference task pages exist only in the `composer check:reference` copy, the print page was not looked at at 390px, and the real two-step confirmation dialog was not driven. Tests cover their behaviour, not their look. | Same as above. |
| 2026-11-15 | docs/golden-principles.md (principle 10) | 32 of the `ui` rules are tagged `[review]`: only the reviewer, reading code and screenshots, enforces them; 7 have an oxlint rule. | Same as above; each rule that a machine could check is a separate decision. |
