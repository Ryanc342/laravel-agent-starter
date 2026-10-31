# Golden principles

The reviewer and the weekly cleanup check the code against this list. Keep it short; each line must be checkable.

1. Every business rule is written once, in its owner (ARCHITECTURE.md). Every other place calls it.
2. One write is one Action. Two entry points doing the same thing call the same Action.
3. React only displays and submits. Prices, totals, statuses, deadlines, permissions and eligibility come from the backend.
4. Input is parsed at the edge: FormRequests and `$request->enum()`. Nothing deeper re-validates raw input.
5. Permission is checked once, in the controller, through the Policy.
6. Money is stored as integer cents and multiplied with `Brick\Math\BigDecimal`.
7. No Service, Repository or DTO layers. Use Actions, scopes and Resources.
8. Use what PHP, Laravel and installed packages already provide. Do not hand-write dates, money, parsing or formatting.
9. Every Action and every endpoint has a feature test; buttons and form choices have a consistency test.
10. Files stay small (under 250 lines). A page that grows is split into components.
