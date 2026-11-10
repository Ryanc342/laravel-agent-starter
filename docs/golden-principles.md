# Golden principles

Every rule of this template, grouped under ten principles. The reviewer and the weekly cleanup check the code against this list. Details and examples are in ARCHITECTURE.md and docs/rules/.

Each rule carries two tags. The first says who checks it:

- `[PHPStan]` the PHP linter (phpstan.neon and Ezdev's rules in lint/phpstan/) and `[oxlint]` the frontend linter (the `lint` block in vite.config.ts and Ezdev's rules in lint/oxlint/ezdev.js). A linter reads the code without running it. Both run in `composer check`; the change cannot be committed until they pass.
- `[Structure]` tests/Structure: checks a linter does not do. DocsTest.php reads the docs, CodeTest.php and the other files read the code across folders and the configs, ResourceTypesTest.php compares each Resource with its TypeScript type (kept until a tagged Wayfinder release generates the types, see docs/rules/frontend.md). Also in `composer check`.
- `[hook]` the finish check (scripts/hooks/finish-check.php). Claude Code and Codex run it when the AI is about to stop; it compares the work with the start of the conversation and sends back what is missing. After three times it lets the AI stop and tells the user, and the next conversation, which step is still missing.
- `[user]` the user sees it in the conversation.
- `[review]` the `reviewer` subagent reads the change for it, and the weekly cleanup reads the whole repository for it. No machine can tell it from the code.

The second says what the rule is about:

- `structure`: where code goes, which layer may use which, what a class or file looks like, which companion files it needs.
- `business`: the business logic itself is correct and written once.
- `process`: the steps of the work.
- `ui`: how a screen looks and behaves for the person using it (DESIGN.md).

Machines check `structure` and `process` rules only where they can tell by position, imports, calls and files. Whether code decides a business rule needs someone who reads it, so `business` rules, and the `structure` rules that need reading, belong to the reviewer. Whether a screen looks right needs someone who looks at it, so the `ui` rules are the reviewer's too, checked on screenshots.

## 1. Every business rule is written once

- `[PHPStan]` `structure` Query conditions (`where…`, `orWhere…`) are written only in model scopes. Everywhere else calls the scope by name; `whereKey()` is allowed for one record.
- `[review]` `business` Inside a model, a column that a scope filters on is not compared again in a method: the method asks the scope with `whereKey($this->getKey())->exists()`.
- `[review]` `structure` Outside app/Models and app/Enums nothing compares a model field, an enum case or an enum's value (`===`, `<`, `match`, `switch`, `in_array`, `isPast()`), including `$this->field` in a Resource. Policies, Resources, Actions and controllers call the model or enum method instead.
- `[review]` `business` No copied blocks of code: a copied block becomes one Action, scope, model method or shared component.
- `[review]` `business` Two entry points that do the same thing call the same Action.
- `[review]` `business` Factories set values through enum cases and states; they never restate a condition.
- `[review]` `business` Two conditions that mean the same thing but are written differently.

## 2. Layers only depend downwards

- `[PHPStan]` `structure` Enums use no models, policies, actions, http or the request.
- `[PHPStan]` `structure` Models use no policies, actions, http, the request or the Gate.
- `[PHPStan]` `structure` Policies use no actions, http or the request.
- `[PHPStan]` `structure` Actions use no HTTP classes or helpers (request, response, session, Inertia, `request()`, `auth()`, `abort()`, `redirect()`).
- `[PHPStan]` `structure` Resources and FormRequests call no Actions.
- `[PHPStan]` `structure` Notifications use no actions, http or the request.
- `[PHPStan]` `structure` Controllers use no `DB` facade.
- `[PHPStan]` `structure` Integrations are used only by Actions; the HTTP client (`Http::`) only inside app/Integrations.
- `[PHPStan]` `structure` The app never uses seeders, test code, or a factory outside its model's `HasFactory` annotation.

## 3. Every class does only its own job

- `[PHPStan]` `structure` An Action is final, has one public method, `handle()`, and does one operation.
- `[PHPStan]` `structure` An Action's `handle()` takes no `bool` parameter (typed or in the docblock): a switch between two behaviours is two Actions.
- `[review]` `business` A bulk operation loops over the single-record Action instead of copying its checks and writes.
- `[review]` `business` Writes that must succeed together run in one `DB::transaction()`.
- `[review]` `structure` A controller method authorizes, calls one Action (or runs a scoped query into a Resource) and returns. No business `if`.
- `[review]` `structure` Every list page is paginated (`paginate(20)` unless the plan agreed another size, and `<Pagination>` from components/shared). A short list inside another page, such as the user's passkeys on the security page, is not a list page.
- `[PHPStan]` `structure` Controllers live in app/Http/Controllers and have only the standard methods (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `__invoke`); app/Http holds only Controllers, Requests, Resources, Middleware and Fortify. Any other operation gets its own controller.
- `[PHPStan]` `structure` Only Actions write: `save*`, `update*`, `delete*`, `create*`, `insert*`, `upsert`, `increment`, `touch`, `sync*`, `attach`, `DB::statement()`, `Mail::send()`, `notify()`, `markAsRead()` and the rest are rejected everywhere else (seeders and factories in database/ excepted).
- `[PHPStan]` `structure` Model scopes use the `#[Scope]` attribute, not `scopeFoo()` methods.
- `[review]` `structure` Policy abilities call model and enum methods.
- `[PHPStan]` `structure` app/Enums holds enums only. Facts about a value are enum methods.
- `[PHPStan]` `structure` A Resource lists each field by name: `parent::toArray()`, `$this->resource->toArray()`, `getAttributes()` and the like, `(array) $this->resource`, and a relation sent without its own Resource (`$this->whenLoaded('lines')` alone) are rejected inside Resources.
- `[review]` `structure` A file over 300 lines is a signal, not an error: check whether it does more than one job, and split it by job, never by size alone.

## 4. The frontend never does the backend's job

React never calculates prices, totals, statuses, deadlines, permissions or eligibility. The Resource sends the results (`is_*`, `can.*`). The linter checks what React may import and call; whether React decides a business rule needs someone who reads the code, so those rules are the reviewer's.

- `[PHPStan]` `structure` Every model reaches React through a JsonResource: the props of `Inertia::render()`, `inertia()`, `->with()`, `Inertia::share()`, the shared props and `response()->json()` never hold a model, a model collection or a paginator of models, also not as `->toArray()`, inside an array, a closure or `Inertia::defer()` and the like.
- `[review]` `structure` React does not check permissions (comparisons with `auth.user`). Buttons are shown from `can.*`.
- `[review]` `structure` React does not compare statuses, roles or types, under any name. It shows the `is_*` flags and labels.
- `[review]` `structure` React does not compare or subtract dates. Display formatting goes in resources/js/lib/format.ts.
- `[review]` `structure` React does not calculate money or sum lists. Dividing integer cents by 100 inside a currency formatter in resources/js/lib/format.ts is display; totals, discounts and taxes come from the Resource.
- `[review]` `structure` React decides no other business rule either (eligibility, deadlines, limits): the Resource sends the result.
- `[oxlint]` `structure` No `fetch` or `axios`: requests go through Inertia.
- `[review]` `structure` No hard-coded URLs: routes and controller actions come from Wayfinder (`@/routes`, `@/actions`).
- `[Structure]` `structure` Each Resource has a TypeScript type with the same fields, marked "Matches app/Http/Resources/{Model}Resource.php".
- `[oxlint]` `structure` No UI library besides components/ui (shadcn): importing another UI library in React code is rejected.
- `[Structure]` `structure` No UI library that comes in through CSS only: resources/css imports only Tailwind and tw-animate-css, and package.json has no CSS UI kit (daisyui, Bootstrap and the like).
- `[review]` `structure` Select options come from the list the backend sends, as `{ value, label }` pairs from one model or enum method with one query for the whole list, never a full Resource or a query per option. Display preferences kept in the browser (the appearance setting) are not business options.
- `[review]` `structure` Validation lives in the FormRequest; React adds only simple HTML attributes (`required`, `maxLength`, `min`, `max`, `step`). Keeping submit disabled until the input is complete (every digit of a code, a name that is not blank) is display.
- `[review]` `business` A `can.*` flag includes the business state, so a button shows exactly when pressing it succeeds. A reason shown for a refusal comes from the Resource, never worked out from `!can.*` or a mix of flags.

## 5. Input is checked once, at the edge

- `[PHPStan]` `structure` A controller reads input only from a FormRequest: `$request->validated()`, `safe()` or a method the FormRequest declares. `input()`, `all()`, `query()`, `boolean()`, `enum()`, `$request->field` and the Request facade are rejected in controllers. An endpoint with no input (mark all as read) needs no FormRequest; a list page with filters gets one.
- `[review]` `business` A record the user must own that is picked in a form (an id in the input) is looked up through its owner scope in the Action, with one message for "missing" and "not yours"; the FormRequest has no `exists` rule for it. A record in the URL (route model binding) is checked by the Policy (403).
- `[PHPStan]` `structure` `$request->validate()`, the `Validator` facade and `validator()` are rejected: input is validated by a FormRequest's `rules()`. Only the Fortify adapters in app/Http/Fortify may use them.
- `[PHPStan]` `structure` A FormRequest checks the shape of the input only: no database queries in FormRequests or app/Concerns, apart from the `exists` and `unique` rules.
- `[PHPStan]` `structure` FormRequests have no `authorize()`. Permission is checked in the controller.
- `[PHPStan]` `structure` `Enum::from()` and `tryFrom()` only where input arrives (FormRequests, Actions, console commands, the enum itself).
- `[PHPStan]` `structure` Permission is checked once, in the controller, through the Policy. Actions never call the Gate or `can()`; enums, models, policies and notifications never use the Gate.

## 6. Keep it simple

- `[PHPStan]` `structure` No Service, Repository or DTO classes and no interfaces, unless the user asks for them.
- `[Structure]` `structure` app/ holds only Actions, Concerns, Console, Enums, Http, Integrations, Models, Notifications, Policies, Providers and Support; a new top-level folder is a decision for the user.
- `[PHPStan]` `structure` No events or listeners: `event()`, the `Event` facade and the event dispatcher, model event callbacks (`creating()`, `saved()`, `observe()` and the like), `#[ObservedBy]`, `$dispatchesEvents` and classes in app/Events, app/Listeners or app/Observers are rejected. An Action may call another Action.
- `[review]` `business` No code for a state nothing can reach yet: no refusal or branch for a status the app cannot create, and no test stand-in for it. The feature that adds the state adds its check and a real test.
- `[review]` `business` Lock rows only where concurrent writes can collide, such as a number counter or stock. Reading never locks, also not a row an Action only reads to check it (whether a supplier is active).
- `[PHPStan]` `structure` `lockForUpdate()` and `sharedLock()` are called only in Actions, and an Action that locks has `tests/Feature/{Domain}/{Action}LockTest.php` (docs/rules/tests.md, Row locks).
- `[review]` `business` Money is stored as integer cents, multiplied with `Brick\Math\BigDecimal` and rounded with its `RoundingMode`, never with `round()` or `number_format()`.
- `[review]` `business` Use what PHP, Laravel and installed packages already provide. Do not hand-write dates, money, parsing or formatting: React formats with `Intl` in resources/js/lib/format.ts, never with `toFixed()` or `padStart()`.

## 7. Files go where the conventions say

- `[PHPStan]` `structure` Class names end in `Controller`, `Request`, `Resource` and `Policy` in their folders.
- `[PHPStan]` `structure` Every model has a factory in database/factories with the states tests need.
- `[PHPStan]` `structure` Actions live in `app/Actions/{Domain}/`, one folder per business domain.
- `[review]` `structure` An Action is named `{Verb}{Noun}` (`SendQuote`, `CompleteOverdueTasks`).
- `[Structure]` `structure` Every route is named, no route is a closure in routes/, and a fixed path comes before a wildcard path of the same method.
- `[review]` `structure` One route file per domain, and routes bind models by their URL parameter (route model binding).
- `[Structure]` `structure` Pages live in `resources/js/pages/{domain}/`; the components root holds only the starter kit's layout and general components (app-shell, heading, input-error), so new components go in `components/{domain}/` or `components/shared/`; components/ui is shadcn's code and imports no domain code.
- `[oxlint]` `structure` Domain components never import another domain's components; components/shared never imports domain components; nothing imports a page; no circular imports.
- `[Structure]` `structure` Code lives only where the checks read it: resources/js holds only `.ts` and `.tsx` files in its fixed folders, docs/ holds only Markdown (docs/reference excepted), PHPStan covers every autoload folder, views and public/ hold no PHP (public/index.php excepted), nothing is required from a non-PHP file, and there are no symlinks.
- `[Structure]` `structure` AGENTS.md stays under 100 lines and every path it names exists. Every ExecPlan has the sections docs/PLANS.md requires. Every rule here carries its two tags.

## 8. Every feature is tested

- `[PHPStan]` `structure` Every Action has `tests/Feature/{Domain}/{Action}Test.php`.
- `[PHPStan]` `structure` Every Resource that sends `can.*` has a `{Model}ButtonsTest.php` consistency test.
- `[review]` `business` Every form's choices have a consistency test: a value is accepted exactly when it is in the list the page receives.
- `[review]` `business` Every endpoint is tested for guests, other users (403), invalid input and success.
- `[Structure]` `structure` Test data comes from factories: `DB::insert()`, `DB::table(…)->insert()`, `Model::create()`, `insert()`, `upsert()` on models and `INSERT` statements in tests/ are rejected.
- `[Structure]` `structure` Pest test files end in `Test.php` inside tests/Feature or tests/Unit; Vitest files are `tests/js/**/*.test.ts(x)`.
- `[review]` `structure` A hook with timing or ordering logic (wait before asking, the latest answer wins, retry) has a Vitest test in tests/js.
- `[Structure]` `structure` resources/js/lib/format.ts, the money, quantity and date formatters, has `tests/js/lib/format.test.ts`.
- `[review]` `process` A lock test fails when its `lockForUpdate()` is removed: try it once.
- `[review]` `process` When a test fails, fix the code, not the test.

## 9. Work follows the steps

docs/workflow.md has the steps. A request to fix tech debt or review findings is a request like any other.

- `[user]` `process` Every request starts with questions, and code waits for the user's "start".
- `[hook]` `process` Changed code needs a plan written or updated in this conversation, with its purpose, agreed decisions and milestones filled in. A one-line change to an existing file, with no open decision, may skip it.
- `[hook]` `process` A changed protected file or dependency (AGENTS.md "Red lines") has the user's approval in a Decision Log line added in this conversation, naming the file.
- `[hook]` `process` Removing a line from docs/exec-plans/tech-debt.md updates that domain in docs/QUALITY.md.
- `[hook]` `process` An intermediate stop with a valid active plan containing completed and remaining milestones may use a fresh `composer check:milestone` result: full static checks plus affected tests. Final acceptance, completed/archived plans and code without such a plan require full `composer check` on the current code. Partial results never count as full passes. A finished plan moves to completed/, with full check results and review rounds in Progress and a filled Outcomes & Retrospective.
- `[hook]` `process` Checks share a worktree lock and bind their start, finish and pass to one run. Starting a new attempt revokes old passes; failed or interrupted attempts cannot certify code. Completing or newly archiving a plan requires a real current full pass across conversations, including a previously refused archive; historical record corrections do not alone count as new delivery.
- `[user]` `process` Each milestone records its affected behavior and callers, selected test paths, commands and results. Shared test/database infrastructure or impact that cannot be bounded requires an earlier full check. Changed pages still receive UI inspection; review code fixes require final full validation (docs/workflow.md).
- `[Structure]` `process` No check is silenced: no `@phpstan-ignore`, `oxlint-disable`, `eslint-disable`, `@ts-ignore` or `@ts-expect-error`, no skipped or unfinished test (Pest fails on them), and no `ignoreErrors`, `excludePaths` or baseline in phpstan.neon.
- `[Structure]` `process` The checks stay as set: PHPStan's level, paths, includes and rules, every Ezdev oxlint rule on with only generated code ignored, strict TypeScript, the three Pest suites, the steps of `composer check`, no other lint config file, and no `script-shell` in .npmrc. Changing them is a protected change.

## 10. Screens follow DESIGN.md

DESIGN.md says how the app looks; docs/rules/visual-design.md says how to apply it. The reviewer checks these rules in the changed code and on screenshots of the changed pages.

- `[review]` `ui` Colors, fonts, corners and spacing come from the theme tokens DESIGN.md sets in resources/css/app.css: no raw colors (`bg-blue-500`, `#hex`), no arbitrary sizes (`text-[13px]`), no `dark:` color overrides.
- `[review]` `ui` One primary color, for the main action of a screen; `destructive`, `success` and `warning` only mean a status, never decoration.
- `[review]` `ui` Cards hold only repeated items, dialogs and framed tools: no card inside a card, no page section styled as a floating card, and corners from the radius scale.
- `[review]` `ui` The page title is `<Heading>` and sections, panels, rows and tables use smaller headings; nothing in the app uses marketing-size type; one font family, as DESIGN.md sets.
- `[review]` `ui` None of the looks DESIGN.md's Don'ts name: gradients, glows, decorative backgrounds, a row of identical stat cards as the page opener, all-caps or letter-spaced labels.
- `[review]` `ui` The first screen after login is the working screen, and the layout does the explaining: no secondary labels or descriptions, no description under a heading unless the title alone is unclear, no slogans, no text about how the screen was built or what was asked for, and nothing shown twice.
- `[review]` `ui` Explaining text appears only for an unusual state (an error, an empty list, a locked record) or a step the user must take; other details sit behind a Tooltip, a collapsible section or a detail page.
- `[review]` `ui` Pages of the same kind (list, detail, form) use the layout DESIGN.md describes and the reference feature shows: header row with the heading and the page's actions, then filters, content and pagination. A list of records is a Table, with its name, status and amount first.
- `[review]` `ui` A page about one record has a breadcrumb that ends at that record, built from its data, and no separate back button.
- `[review]` `ui` At desktop and phone width, text stays inside its box and nothing overlaps; long text wraps or truncates, and a wide table scrolls inside its own container.
- `[review]` `ui` An existing component (components/ui, components/shared) is used instead of hand-styled markup, and restyled through its variants, not by overriding its colors with `className`.
- `[review]` `ui` Familiar controls: a Select for a list of options, a Checkbox for yes or no, a ToggleGroup for two to seven modes, tabs for views; an icon-only button has a Tooltip and an `aria-label`.
- `[review]` `ui` An empty list shows a short message, data that arrives later shows a Skeleton, and an action that cannot be undone asks for confirmation in a Dialog first.
- `[review]` `ui` Every input has a label. It is visible unless the layout already says what the field is (a search box next to a Search button), and then it is hidden with `sr-only` or `aria-label`, never left out. Errors show next to the field with `InputError`, and the submit button is disabled while the form is sending.
- `[review]` `ui` Keyboard focus stays visible, actions are buttons and navigation is links (no clickable `div`), text is readable against its background, and light and dark mode both work.
- `[review]` `ui` Button labels say what happens ("Save changes", "Delete task", not "Submit" or "OK"), and an error message says how to fix the problem.
- `[review]` `ui` A change to a screen changes only what the plan names: the rest of the page stays as it was, and removing or simplifying is preferred over adding.
- `[review]` `ui` Every changed page is opened with isolated headless Playwright at desktop (1280px) and phone (390px) width, and once in dark mode, and the rules above are checked on temporary screenshots before closing the browser (docs/rules/visual-design.md).
