# Golden principles

Every rule of this template, grouped under nine principles. The reviewer and the weekly cleanup check the code against this list. Details and examples are in ARCHITECTURE.md and docs/rules/.

Each rule says who stops it:

- `[PHPStan]` the PHP linter (phpstan.neon and Ezdev's rules in lint/phpstan/) and `[oxlint]` the frontend linter (the `lint` block in vite.config.ts and Ezdev's rules in lint/oxlint/ezdev.js). A linter reads the code without running it. Both run in `composer check`; the change cannot be committed until they pass.
- `[Structure]` tests/Structure: checks a linter does not do. DocsTest.php reads the docs, CodeTest.php reads files across folders, ResourceTypesTest.php compares each Resource with its TypeScript type (kept until a tagged Wayfinder release generates the types, see docs/rules/frontend.md). Also in `composer check`.
- `[hook]` the finish check (scripts/hooks/finish-check.php). Claude Code and Codex run it when the AI is about to stop; it compares the work with the start of the conversation and sends back what is missing.
- `[user]` the user sees it in the conversation.
- `[review]`: no machine can check it. The `reviewer` subagent looks for it in every change and the weekly cleanup looks for it in the whole repository.

There are at most 27 `[review]` rules (tests/Structure/DocsTest.php counts them). To add one, merge or remove another, or turn one into a check.

## 1. Every business rule is written once

- `[PHPStan]` Query conditions (`where…`, `orWhere…`) are written only in model scopes. Everywhere else calls the scope by name; `whereKey()` is allowed for one record.
- `[PHPStan]` Inside a model, a column that a scope filters on is not compared again in a method: the method asks the scope with `whereKey($this->getKey())->exists()`.
- `[PHPStan]` Outside app/Models and app/Enums nothing compares a model field, an enum case or an enum's value (`===`, `<`, `match`, `switch`, `in_array`), including `$this->field` in a Resource. Policies, Resources, Actions and controllers call the model or enum method instead.
- `[review]` No copied blocks of code: a copied block becomes one Action, scope, model method or shared component.
- `[review]` Two entry points that do the same thing call the same Action.
- `[review]` Factories set values through enum cases and states; they never restate a condition.
- `[review]` Two conditions that mean the same thing but are written differently.

## 2. Layers only depend downwards

- `[PHPStan]` Enums use no models, policies, actions or http.
- `[PHPStan]` Models use no policies, actions or http.
- `[PHPStan]` Policies use no actions or http.
- `[PHPStan]` Actions use no HTTP classes or helpers (request, response, Inertia, `request()`, `redirect()`).
- `[PHPStan]` Resources and FormRequests call no Actions.
- `[PHPStan]` Notifications use no actions or http.
- `[PHPStan]` Controllers use no `DB` facade.
- `[PHPStan]` Integrations are used only by Actions; the HTTP client (`Http::`) only inside app/Integrations.

## 3. Every class does only its own job

- `[PHPStan]` An Action is final, has one public method, `handle()`, and does one operation.
- `[PHPStan]` An Action's `handle()` takes no `bool` parameter (typed or in the docblock): a switch between two behaviours is two Actions.
- `[review]` A bulk operation loops over the single-record Action instead of copying its checks and writes.
- `[review]` Writes that must succeed together run in one `DB::transaction()`.
- `[review]` A controller method authorizes, calls one Action (or runs a scoped query into a Resource) and returns. No business `if`.
- `[review]` Every list page is paginated (`paginate(20)` unless the plan agreed another size, and `<Pagination>` from components/shared). A short list inside another page, such as the user's passkeys on the security page, is not a list page.
- `[PHPStan]` Controllers have only the standard methods (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `__invoke`). Any other operation gets its own controller.
- `[PHPStan]` Only Actions write: `save`, `update`, `delete`, `create`, `notify()`, `markAsRead()` and the rest are rejected everywhere else (seeders and factories in database/ excepted).
- `[PHPStan]` Model scopes use the `#[Scope]` attribute, not `scopeFoo()` methods.
- `[review]` Policy abilities call model and enum methods.
- `[PHPStan]` app/Enums holds enums only. Facts about a value are enum methods.
- `[PHPStan]` A Resource lists each field by name: `parent::toArray()`, `$this->resource->toArray()`, `getAttributes()` and the like, and `(array) $this->resource` are rejected inside Resources.
- `[review]` A file over 300 lines is a signal, not an error: check whether it does more than one job, and split it by job, never by size alone.

## 4. The frontend never does the backend's job

React never calculates prices, totals, statuses, deadlines, permissions or eligibility. The Resource sends the results (`is_*`, `can.*`). The oxlint rules below read the React code by name and shape; a business rule written under other names is still the reviewer's job.

- `[PHPStan]` Every model reaches React through a JsonResource: the props of `Inertia::render()` and the shared props never hold a model, a model collection or a paginator of models, also not inside an array, a closure or `Inertia::defer()` and the like.
- `[oxlint]` React does not check permissions (comparisons with `auth.user`). Buttons are shown from `can.*`.
- `[oxlint]` React does not compare statuses, roles or types (`.status ===`, a bare `status` or `role`, `switch (x.status)`, `.includes(x.status)`), also through a variable they were copied into (`const s = quote.status`). It shows the `is_*` flags and labels.
- `[oxlint]` React does not compare or subtract dates (`new Date(…) <`, `Date.now() -`, `getTime()`) outside resources/js/lib/format.ts.
- `[oxlint]` React does not calculate money or sum lists (arithmetic on `price`, `total`, `amount` and similar fields, `+=`, a `reduce` that adds) outside resources/js/lib/format.ts.
- `[oxlint]` React does not format numbers or dates by hand: no `toFixed()`, `padStart()` or `padEnd()`, resources/js/lib/format.ts included. Display formatting uses `Intl`.
- `[oxlint]` No hard-coded URLs, `fetch` or `axios`: routes and controller actions come from Wayfinder, requests go through Inertia.
- `[Structure]` Each Resource has a TypeScript type with the same fields, marked "Matches app/Http/Resources/{Model}Resource.php".
- `[oxlint]` No UI library besides components/ui (shadcn): importing another UI library in React code is rejected.
- `[review]` No UI library that comes in through CSS only (a Tailwind plugin such as daisyui, Bootstrap's stylesheet).
- `[review]` Select options come from the list the backend sends. Display preferences kept in the browser (the appearance setting) are not business options.
- `[review]` Validation lives in the FormRequest; React adds only simple HTML attributes (`required`, `maxLength`, `min`, `max`, `step`). Keeping submit disabled until the input is complete (every digit of a code, a name that is not blank) is display.
- `[review]` A business rule React decides under names the checks do not know.
- `[review]` A `can.*` flag includes the business state, so a button shows exactly when pressing it succeeds. A reason shown for a refusal comes from the Resource, never worked out from `!can.*` or a mix of flags.

## 5. Input is checked once, at the edge

- `[PHPStan]` A controller reads input only from a FormRequest: `$request->validated()`, `safe()` or a method the FormRequest declares. `input()`, `all()`, `query()`, `boolean()`, `enum()`, `$request->field` and the Request facade are rejected in controllers. An endpoint with no input (mark all as read) needs no FormRequest; a list page with filters gets one.
- `[review]` A record the user must own that is picked in a form (an id in the input) is looked up through its owner scope in the Action, with one message for "missing" and "not yours"; the FormRequest has no `exists` rule for it. A record in the URL (route model binding) is checked by the Policy (403).
- `[PHPStan]` `$request->validate()`, `Validator::make()` and `validator()` are rejected: input is validated by a FormRequest's `rules()`. Only the Fortify adapters in app/Http/Fortify may call `Validator::make()` and `validator()`.
- `[PHPStan]` A FormRequest checks the shape of the input only: no database queries in FormRequests or app/Concerns, apart from the `exists` and `unique` rules.
- `[PHPStan]` FormRequests have no `authorize()`. Permission is checked in the controller.
- `[PHPStan]` `Enum::from()` and `tryFrom()` only where input arrives (FormRequests, Actions, console commands, the enum itself).
- `[PHPStan]` Permission is checked once, in the controller, through the Policy. Actions never call the Gate or `can()`.

## 6. Keep it simple

- `[PHPStan]` No Service, Repository or DTO classes and no interfaces, unless the user asks for them.
- `[PHPStan]` No events or listeners: `event()`, the `Event` facade and the event dispatcher, model event callbacks (`creating()`, `saved()`, `observe()` and the like), `#[ObservedBy]`, `$dispatchesEvents` and classes in app/Events, app/Listeners or app/Observers are rejected. An Action may call another Action.
- `[review]` No code for a state nothing can reach yet: no refusal or branch for a status the app cannot create, and no test stand-in for it. The feature that adds the state adds its check and a real test.
- `[review]` Lock rows only where concurrent writes can collide, such as a number counter or stock. Reading never locks.
- `[PHPStan]` `lockForUpdate()` and `sharedLock()` are called only in Actions, and an Action that locks has `tests/Feature/{Domain}/{Action}LockTest.php` (docs/rules/tests.md, Row locks).
- `[review]` Money is stored as integer cents and multiplied with `Brick\Math\BigDecimal`.
- `[PHPStan]` No `number_format()`, `round()`, `floor()` or `ceil()` in app/: money is rounded with `BigDecimal` and a `RoundingMode`, and React formats the display in resources/js/lib/format.ts.
- `[review]` Use what PHP, Laravel and installed packages already provide. Do not hand-write dates, money, parsing or formatting.

## 7. Files go where the conventions say

- `[PHPStan]` Class names end in `Controller`, `Request`, `Resource` and `Policy` in their folders.
- `[PHPStan]` Every model has a factory in database/factories with the states tests need.
- `[review]` Actions live in `app/Actions/{Domain}/{Verb}{Noun}.php`.
- `[review]` One route file per domain. Every route is named and uses route model binding; fixed paths come before wildcard paths.
- `[review]` Pages live in `resources/js/pages/{domain}/`, components in `resources/js/components/{domain}/` or `components/shared/`. The starter kit's layout and general components (app-shell, heading, input-error) stay at the components root. components/ui is shadcn's code, which the linter skips; it never imports a domain component.
- `[oxlint]` Domain components never import another domain's components; components/shared never imports domain components; nothing imports a page; no circular imports.
- `[Structure]` AGENTS.md stays under 100 lines and every path it names exists. Every ExecPlan has the sections docs/PLANS.md requires.

## 8. Every feature is tested

- `[PHPStan]` Every Action has `tests/Feature/{Domain}/{Action}Test.php`.
- `[PHPStan]` Every Resource that sends `can.*` has a `{Model}ButtonsTest.php` consistency test.
- `[review]` Every form's choices have a consistency test: a value is accepted exactly when it is in the list the page receives.
- `[review]` Every endpoint is tested for guests, other users (403), invalid input and success.
- `[Structure]` Test data comes from factories: `DB::insert()` and `DB::table(…)->insert()` in tests/ are rejected.
- `[review]` A hook with timing or ordering logic (wait before asking, the latest answer wins, retry) has a Vitest test in tests/js.
- `[Structure]` resources/js/lib/format.ts, the money and date formatters, has `tests/js/lib/format.test.ts`.
- `[review]` When a test fails, fix the code, not the test.

## 9. Work follows the steps

docs/workflow.md has the steps. A request to fix tech debt or review findings is a request like any other.

- `[user]` Every request starts with questions, and code waits for the user's "start".
- `[hook]` Changed code needs a plan in docs/exec-plans/active/. A one-line change to an existing file, with no open decision, may skip it.
- `[hook]` A changed protected file or dependency (AGENTS.md "Red lines") has the user's approval in the plan's Decision Log, naming the file.
- `[hook]` Removing a line from docs/exec-plans/tech-debt.md updates that domain in docs/QUALITY.md.
- `[hook]` The work stops only after `composer check` passed on the code as it is, and a plan moved to completed/ has its check results and review rounds in Progress and a filled Outcomes & Retrospective.
- `[Structure]` No check is silenced: no `@phpstan-ignore`, `oxlint-disable`, `eslint-disable`, `@ts-ignore` or `@ts-expect-error`, no skipped test, and no `ignoreErrors` or baseline in phpstan.neon.
