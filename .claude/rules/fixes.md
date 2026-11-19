# Fixes

A fix removes the cause of a problem. Hiding the problem is not a fix. This file has no `paths:`, so it applies to every change. A rule names its check in its tag, with its lane and level, and says why in one sentence (docs/golden-principles.md).

- `[review:backend:1]` `[review:frontend:1]` An error is never swallowed: when its cause cannot be fixed in this plan, the error shows and goes into the report. Why: a hidden failure (a `catch` after which the page still says "Saved") tells the user something false.
- `[review:backend:1]` `[review:frontend:1]` A case the code does not handle is made impossible or handled, never covered with a default. Why: an unknown status shown as "Paid", or a missing price counted as 0, is wrong data.
- `[review:backend:1]` `[review:frontend:1]` A failure is never retried to get past it: its cause is found and removed, and when two people save the same record at once, the row is locked (.claude/rules/actions.md). Why: a retry hides the cause and may repeat what must happen once. Not a break: asking an outside service once more after its network failed for a moment, when sending the request twice does no harm (an exchange rate may be asked again, a card may not be charged again).

A cause you cannot find goes into the report. It is never hidden in one of the three ways above.
