# Fixes

A fix removes the cause of a problem. Hiding the problem is not a fix. This file has no `paths:`, so it applies to every change. A rule names its check in its tag (docs/golden-principles.md).

- `[review:business]` `[review:frontend]` Do not swallow an error. Example: the save fails, a `catch` hides the failure, and the page still says "Saved". Instead, fix why the save fails; if that is not possible in this plan, let the error show and put it in the report.
- `[review:business]` `[review:frontend]` Do not cover a case the code does not handle with a default. Example: an unknown status is shown as "Paid", or a missing price is counted as 0. Instead, make the case impossible (only known statuses can be stored) or handle it; a missing value is an error, not a silent 0.
- `[review:business]` `[review:frontend]` Do not retry to get past a failure. Example: saving again and again until it happens to work. Instead, find why it fails and remove the cause; when two people save the same record at once, lock the row (.claude/rules/actions.md).

One retry hides nothing and stays allowed: asking an outside service again after its network failed for a moment, and only when sending the request twice does no harm. Looking up an exchange rate may be retried; charging a card may not, because the card would be charged twice (.claude/rules/actions.md, External services).

A cause you cannot find goes into the report. It is never hidden in one of the three ways above.
