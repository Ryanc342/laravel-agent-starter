# Golden principles

Every rule of this template, grouped under principles and listed once, under the review lane that reads it. A reviewer reads this introduction and the section of its own lane (docs/prompts/review.md); the weekly cleanup reads them all. Details and examples are in ARCHITECTURE.md and docs/rules/.

Each rule carries two tags. The first says who checks it:

- `[PHPStan]` the PHP linter (the layer rules in phpstan.neon) and `[oxlint]` the frontend linter (the `lint` block in vite.config.ts and Ezdev's rules in lint/oxlint/ezdev.js). A linter reads the code without running it. Both run in `composer check`; the change cannot be committed until they pass.
- `[Structure]` tests/Structure: checks a linter does not do. FoldersTest.php and CodeTest.php read where files are and what they hold, ConfigTest.php reads the configs, DesignDocsTest.php reads the design spec. tests/Unit/Lint/SkeletonChecksTest.php breaks every machine rule once in a fixture and proves that its check still fails. Also in `composer check`.
- `[hook]` a Claude Code hook: the finish check (scripts/hooks/finish-check.php), run when the AI is about to stop, which compares the work with the start of the conversation and sends back what is missing, never twice in a row; and the command check (scripts/hooks/reviewer-commands.php), run before a reviewer's Bash call.
- `[review]` the two reviewers (`reviewer-opus`, `reviewer-haiku`) read the change for it, one lane per run, and the cleanup reads the repository for it. No machine checks it.

The second says what the rule is about:

- `structure`: where code goes, which layer may use which, what a class or file looks like, which companion files it needs.
- `business`: the business logic itself is correct and written once.
- `process`: the steps of the work.
- `ui`: how a screen looks and behaves for the person using it (DESIGN.md and docs/design/).

Machines check the layers (principle 2), the folders of app/, where pages and components go, that code lives where the checks read it, and that no check is silenced or turned down. Everything else needs someone who reads the code or looks at the screen, so it belongs to the reviewers: every `business` rule, the `structure` and `process` rules outside the machine checks, and the `ui` rules, checked on screenshots.

## Review lanes

One reviewer run covers one lane. A change needs rounds only for the lanes it touches: it adds, changes or deletes a file in one of the lane's places.

| Lane                        | Key         | Rules                                     | Touched by files in                                                   |
| --------------------------- | ----------- | ----------------------------------------- | --------------------------------------------------------------------- |
| Business and class jobs     | `business`  | principles 1 and 3                        | app/, database/, routes/                                              |
| Input and placement         | `input`     | principles 5, 6 and 7                     | app/, database/, routes/, resources/js/, composer.json, package.json  |
| Frontend logic              | `frontend`  | principle 4                               | resources/js/, app/Http/Resources/, app/Http/Requests/                |
| Tests                       | `tests`     | principle 8                               | tests/, app/, routes/, resources/js/hooks/, resources/js/lib/         |
| Screens: look               | `look`      | principle 10, Screens: look               | resources/js/, resources/css/, DESIGN.md, docs/design/                |
| Screens: behaviour and text | `behaviour` | principle 10, Screens: behaviour and text | resources/js/, resources/css/, resources/views/, lang/, app/          |

The final review reviews every lane the plan touches, both reviewers at once, and the screens last, when the other lanes are done, so a backend change that breaks a page is still caught at the end (docs/workflow.md, step 4).

A file of the reference feature (docs/reference/tasks/) other than Markdown touches the lanes of its path inside the feature: docs/reference/tasks/resources/js/ as resources/js/, docs/reference/tasks/app/ as app/.

Principles 2 and 9 have no `[review]` rule: the machines and the hooks check them. They come last, after the lanes.

## Lane `business`: principles 1 and 3

### 1. Every business rule is written once

- `[review]` `structure` Query conditions (`where…`, `orWhere…`) are written only in model scopes (database/ and tests/ excepted). Everywhere else calls the scope by name; `whereKey()` and `whereKeyNot()` are allowed for one record, and one record asks the same scope with `whereKey($id)->exists()`.
- `[review]` `business` Inside a model, a column that a scope filters on is not compared again in a method: the method asks the scope with `whereKey($this->getKey())->exists()`.
- `[review]` `structure` Outside app/Models and app/Enums nothing compares a model field, an enum case or an enum's value (`===`, `<`, `match`, `switch`, `in_array`, `isPast()`), including `$this->field` in a Resource. Policies, Resources, Actions and controllers call the model or enum method instead.
- `[review]` `business` No copied blocks of code: a copied block becomes one Action, scope, model method or shared component.
- `[review]` `business` Two entry points that do the same thing call the same Action.
- `[review]` `business` Factories set values through enum cases and states; they never restate a condition.
- `[review]` `business` Two conditions that mean the same thing but are written differently.

### 3. Every class does only its own job

- `[review]` `structure` An Action is final, has one public method, `handle()` (and a constructor), and does one operation; its other methods are private. app/Actions holds no traits: steps two Actions share go in a third Action that both call.
- `[review]` `structure` An Action's `handle()` takes no `bool` parameter (typed, nullable, `true` or `false`, in a union, or in the docblock): a switch between two behaviours is two Actions (`ArchiveTask` and `RestoreTask`).
- `[review]` `business` A bulk operation loops over the single-record Action instead of copying its checks and writes.
- `[review]` `business` Writes that must succeed together run in one `DB::transaction()`.
- `[review]` `structure` A controller method authorizes, calls one Action (or runs a scoped query into a Resource) and returns. No business `if`.
- `[review]` `structure` Every list page is paginated with the page size its FormRequest reads from `per_page` through `ReadsPerPage` (10, 20, 50 or 100; anything else 20), never `take()`, and shows `<Pagination>` from components/shared with the rows-per-page choice. A short list inside another page, such as the user's passkeys on the security page, is not a list page.
- `[review]` `structure` Controllers live in app/Http/Controllers and have only the standard methods (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `__invoke`, and a constructor; methods from traits count); app/Http holds only Controllers, Requests, Resources, Middleware and Fortify. Any other operation gets its own controller (`NotificationReadController@store`).
- `[review]` `structure` Only Actions write: `save*`, `update*`, `delete*`, `create*`, `firstOrCreate`, `updateOrCreate`, `insert*`, `upsert`, `increment`, `touch`, `truncate`, `destroy`, `sync*`, `attach`, `detach`, `toggle`, `updateExistingPivot`, `associate`, `dissociate`, `DB::statement()`, `Mail::send()`, `Mail::queue()`, `Notification::send()`, `notify()`, `notifyNow()`, `markAsRead()`, `markAsUnread()` and the rest are rejected everywhere else (database/ and tests/ excepted). Move the write into `app/Actions/{Domain}/{Verb}{Noun}.php` and call it.
- `[review]` `structure` Model scopes are protected methods with the `#[Scope]` attribute, not `scopeFoo()` methods.
- `[review]` `structure` Policy abilities call model and enum methods.
- `[review]` `structure` app/Enums holds enums only, no classes or traits (ARCHITECTURE.md, side folders, says where a trait goes). Facts about a value are enum methods.
- `[review]` `structure` A Resource lists each field by name: `parent::toArray()`, `$this->resource->toArray()`, `getAttributes()` and the like, `(array) $this->resource`, `get_object_vars($this->resource)` and a relation sent without its own Resource (`$this->whenLoaded('lines')` alone; wrap it in `LineResource::collection(…)` or `LineResource::make(…)`) are rejected inside Resources, and `toArray()`, `attributesToArray()`, `getAttributes()`, `getOriginal()`, `toJson()` and `jsonSerialize()` on a model are rejected everywhere in app/.
- `[review]` `structure` A file over 300 lines is a signal, not an error: check whether it does more than one job, and split it by job, never by size alone.

## Lane `input`: principles 5, 6 and 7

### 5. Input is checked once, at the edge

- `[review]` `structure` A controller reads input only from a FormRequest: `$request->validated()`, `safe()` or a method the FormRequest declares. `input()`, `all()`, `query()`, `has()`, `boolean()`, `enum()`, `validationData()`, `$request->field`, `$request['field']`, `request('field')` and the Request facade are rejected in controllers; `user()`, `session()`, `route()`, `routeIs()`, `header()`, `ip()`, `expectsJson()`, `url()` and other methods that read no input are allowed. An enum or a date comes from a FormRequest method (`$request->status()`). An endpoint with no input (mark all as read) needs no FormRequest; a list page with filters gets one.
- `[review]` `business` A record the user must own that is picked in a form (an id in the input) is looked up through its owner scope in the Action, with one message for "missing" and "not yours"; the FormRequest has no `exists` rule for it. A record in the URL (route model binding) is checked by the Policy (403).
- `[review]` `structure` `$request->validate()` and `validateWithBag()` are rejected everywhere; the `Validator` facade and `validator()` are rejected except in the Fortify adapters in app/Http/Fortify. Input is validated by the `rules()` of `app/Http/Requests/{Domain}/{Verb}{Model}Request.php`, type-hinted in the controller.
- `[review]` `structure` A FormRequest checks the shape of the input only: no database queries (model, query builder, relation or `DB` calls) in FormRequests or app/Concerns, apart from the `exists` and `unique` rules. Business state (stock, status, ownership, limits) is checked in the Action, which throws `ValidationException::withMessages()`.
- `[review]` `structure` FormRequests have no `authorize()`. Permission is checked in the controller.
- `[review]` `structure` `Enum::from()` and `tryFrom()` only where input arrives (FormRequests, Actions, console commands, the enum itself). A controller gets the enum from a FormRequest method that calls `$this->enum()`; one enum becomes another through an enum method, never through shared values.
- `[review]` `structure` Permission is checked once, in the controller, through the Policy. Actions never call the Gate, `can()`, `canAny()`, `cannot()` or `cant()`; enums, models, policies, notifications and Actions never use the Gate facade or contract or throw `AuthorizationException`. A business state an Action needs is a model method and a `ValidationException`.

### 6. Keep it simple

- `[review]` `structure` No classes whose name ends in Service, Repository or DTO (in any case) and no interfaces, unless the user asks for them: write an Action, query through model scopes, and pass validated arrays, models and enums.
- `[Structure]` `structure` app/ holds only Actions, Concerns, Console, Enums, Http, Integrations, Models, Notifications, Policies, Providers and Support.
- `[review]` `structure` No events or listeners: `event()`, the `Event` facade and the event dispatcher, model event callbacks (`creating()`, `saved()`, `observe()` and the like), `#[ObservedBy]`, `$dispatchesEvents` and classes in app/Events, app/Listeners or app/Observers are rejected. An Action may call another Action.
- `[review]` `business` No code for a state nothing can reach yet: no refusal or branch for a status the app cannot create, and no test stand-in for it. The feature that adds the state adds its check and a real test.
- `[review]` `business` Lock rows only where concurrent writes can collide, such as a number counter or stock. Reading never locks, also not a row an Action only reads to check it (whether a supplier is active).
- `[review]` `structure` `lockForUpdate()`, `sharedLock()` and `lock()` are called only in Actions of an operation that writes, inside its `DB::transaction()` (tests/ excepted; pages and read-only operations never lock). When two or more Actions lock the row of the same model, the lock is written once, in a shared Action `Lock{Model}` that each writing Action calls first inside its transaction: that Action may lock without writing, and it alone has the lock test. An Action that locks has `tests/Feature/{Domain}/{Action}LockTest.php` with `uses(TestsRowLocks::class)` (docs/rules/tests.md, Row locks); when nothing writes the row at the same time, the lock goes.
- `[review]` `business` Money is stored as integer cents, multiplied with `Brick\Math\BigDecimal` and rounded with its `RoundingMode`, never with `round()` or `number_format()`.
- `[review]` `business` Use what PHP, Laravel and installed packages already provide. Do not hand-write dates, money, parsing or formatting: React formats with `Intl` in resources/js/lib/format.ts, never with `toFixed()` or `padStart()`.

### 7. Files go where the conventions say

- `[review]` `structure` Class names end in `Controller`, `Request`, `Resource` and `Policy` in their folders.
- `[review]` `structure` Every model (abstract ones excepted) has `database/factories/{Model}Factory.php` with the states tests need.
- `[review]` `structure` Actions live in `app/Actions/{Domain}/{Verb}{Noun}.php`, one folder per business domain: never directly in app/Actions, never in a deeper folder.
- `[review]` `structure` An Action is named `{Verb}{Noun}` (`SendQuote`, `CompleteOverdueTasks`).
- `[review]` `structure` Every app route is named and no route is a closure in routes/ (`Route::inertia` for a page with no data is fine; package routes such as Fortify's are excepted), and a fixed path comes before a wildcard path of the same method that would catch it (`quotes/create` before `quotes/{quote}`; the fallback route excepted).
- `[review]` `structure` One route file per domain, and routes bind models by their URL parameter (route model binding).
- `[Structure]` `structure` Pages live in `resources/js/pages/{domain}/`; the components root holds only the starter kit's layout and general components (app-shell, heading, input-error), so new components go in `components/{domain}/` or `components/shared/`; components/ui is shadcn's code and imports no domain code.
- `[oxlint]` `structure` Domain components never import another domain's components; components/shared never imports domain components; nothing imports a page; no circular imports.
- `[Structure]` `structure` Code lives only where the checks read it: resources/js holds only `.ts` and `.tsx` files in its fixed folders, docs/ holds only Markdown (docs/reference excepted), PHPStan covers every autoload folder, views and public/ hold no PHP (public/index.php excepted), nothing is required from a non-PHP file, and there are no symlinks.

## Lane `frontend`: principle 4

### 4. The frontend never does the backend's job

React never calculates prices, totals, statuses, deadlines, permissions or eligibility. The Resource sends the results (`is_*`, `can.*`). All of these rules, also what React may import and call, are read by the reviewers (lane `frontend`).

- `[review]` `structure` Every model reaches React through a JsonResource: the props of `Inertia::render()`, `inertia()`, `->with()`, `Inertia::share()`, the shared props and `response()->json()` never hold a model, a model collection or a paginator of models, also not as `->toArray()`, inside an array, a closure or `Inertia::defer()` and the like. Send `{Model}Resource::make($model)->resolve($request)` for one record and `{Model}Resource::collection($models)->response($request)->getData(true)` for a list.
- `[review]` `structure` React does not check permissions (comparisons with `auth.user`). Buttons are shown from `can.*`.
- `[review]` `structure` React does not compare statuses, roles or types, under any name. It shows the `is_*` flags and labels.
- `[review]` `structure` React does not compare or subtract dates to decide anything. Display formatting goes in resources/js/lib/format.ts. "Today" and the current year come from the backend as a shared prop (the date in the app's timezone), never from `new Date()` or `Date.now()`; laying out a month (the grid, the previous and next month) is display and lives only in `lib/month-grid.ts` and `lib/date-value.ts`.
- `[review]` `structure` React does not calculate money or sum lists. Dividing integer cents by 100 inside a currency formatter in resources/js/lib/format.ts is display; totals, discounts and taxes come from the Resource.
- `[review]` `structure` React decides no other business rule either (eligibility, deadlines, limits): the Resource sends the result.
- `[review]` `structure` No `fetch` or `axios` (also `window.fetch`, `window.axios`, `axios/…` and `import('axios')`), in components/ui too: requests go through Inertia, with a Wayfinder route from @/routes or @/actions and `router` or `useForm`.
- `[review]` `structure` No hard-coded URLs: routes and controller actions come from Wayfinder (`@/routes`, `@/actions`).
- `[review]` `structure` Each Resource has a TypeScript type in resources/js/types/ with the same fields and the same `can.*` keys (an extra field only if it is optional), exported right after the comment "Matches app/Http/Resources/{Model}Resource.php"; the Resource's `toArray()` returns one literal array with fixed keys (no spread), and `can` is a literal array.
- `[review]` `structure` No UI library besides components/ui (shadcn): importing another UI library in React code (antd, bootstrap, react-bootstrap, primereact, semantic-ui-react, flowbite, `@headlessui/react`, `@mui`, `@chakra-ui`, `@mantine`, `@nextui-org`, `@heroui`, `@ant-design` and the like) is rejected.
- `[review]` `structure` No UI library that comes in through CSS only: resources/css `@import`s and `@plugin`s only tailwindcss and tw-animate-css, and no dependency section of package.json has a CSS UI kit (daisyui, bootstrap, bulma, flowbite, preline and the like).
- `[review]` `structure` Select options come from the list the backend sends, as `{ value, label }` pairs from one model or enum method with one query for the whole list, never a full Resource or a query per option. Display preferences kept in the browser (the appearance setting) are not business options.
- `[review]` `structure` Validation lives in the FormRequest; React adds only simple HTML attributes (`required`, `maxLength`, `min`, `max`, `step`), so the server's message comes back in the app's language (`noValidate`: lane `behaviour`). Keeping submit disabled until the input is complete (every digit of a code, a name that is not blank) is display.
- `[review]` `business` A `can.*` flag includes the business state, so a button shows exactly when pressing it succeeds. A reason shown for a refusal comes from the Resource, never worked out from `!can.*` or a mix of flags.

## Lane `tests`: principle 8

### 8. Every feature is tested

- `[review]` `structure` Every Action has `tests/Feature/{Domain}/{Action}Test.php`: the happy path and every ValidationException the Action throws.
- `[review]` `structure` Every Resource that sends `can.*` has a `…ButtonsTest.php` in tests/Feature/{Domain}/ that proves every `can.*` flag it sends matches the Policy. The file is named after the model or after the Resource (copy docs/reference/tasks/tests/Feature/Tasks/TaskButtonsTest.php).
- `[review]` `business` Every form's choices have a consistency test: a value is accepted exactly when it is in the list the page receives.
- `[review]` `business` Every endpoint is tested for guests, other users (403), invalid input and success.
- `[review]` `structure` Test data comes from factories: `DB::insert()`, `DB::table(…)->insert()`, `Model::create()` (also through `query()`, `forceCreate`, `createQuietly`, `createOrFirst`, `firstOrCreate` and `updateOrCreate`), `insert()`, `upsert()` and `updateOrInsert()` on models and `INSERT` statements in tests/ (tests/Structure excepted) are rejected. Factories are for tests: apart from the test accounts of database/seeders, no row in the app's own database is made by a factory, and the data for looking at pages comes from the walk through the forms (docs/workflow.md, step 4).
- `[review]` `structure` tests/ holds only Feature, Unit, Structure, Support, js, bootstrap.php, Pest.php and TestCase.php. Every PHP file in tests/Feature or tests/Unit ends in `Test.php` (helpers go in tests/Support). Vitest files are `tests/js/**/*.test.ts(x)`; tests/js holds only those and setup.ts, and no `*.test.*` or `*.spec.*` file exists anywhere else.
- `[review]` `structure` A hook with timing or ordering logic (wait before asking, the latest answer wins, retry) has a Vitest test in tests/js.
- `[review]` `structure` resources/js/lib/format.ts, the money, quantity and date formatters, has `tests/js/lib/format.test.ts` (or `.tsx`).
- `[review]` `process` A lock test fails when its `lockForUpdate()` is removed: try it once.
- `[review]` `process` When a test fails, the code is fixed, not the test: an existing assertion changes or goes only as AGENTS.md "Red lines" says.

## Lane `look`: principle 10, Screens: look

### 10. Screens follow DESIGN.md and docs/design

DESIGN.md says what the style is and which design file to read; docs/design/ holds the details (foundations, components, one file per page pattern); docs/rules/visual-design.md says how to apply them. The reviewers check these rules in the changed code, on screenshots of the changed pages, and against the style overview page, in two lanes: `look` and `behaviour`.

- `[review]` `ui` Colors, fonts, corners and spacing come from the theme tokens in resources/css/app.css that docs/design/foundations.md names.
- `[Structure]` `ui` The spec names tokens and component sizes, never values: no pixel value or Tailwind size class in DESIGN.md or docs/design/ (tests/Structure/DesignDocsTest.php).
- `[Structure]` `ui` Every pattern file in docs/design/patterns/ is in the DESIGN.md index and has its Sample or Demo section, and every file a pattern file names exists (tests/Structure/DesignDocsTest.php).
- `[review]` `ui` Outside components/ui, no Tailwind palette colour (`bg-blue-500`), hex or `rgb()`, `hsl()`, `oklch()` or `oklab()` colour in a class (`className`, a `*ClassName` prop, `cn`, `clsx`, `cva`, `twMerge` or `classNames`) or in `style`, no arbitrary value in a class (an arbitrary variant such as `data-[state=open]:` is fine), and no `dark:` colour override: use a theme token (`bg-primary`, `text-muted-foreground`, `var(--chart-1)`).
- `[review]` `ui` A `components/ui` component (`Button`, `Input`, `Textarea`, `SelectTrigger`, `Toggle`, `ToggleGroupItem`, `Badge`, `Checkbox`, `InputOTPSlot`), `PasswordInput` and `DatePicker` are never resized with an `h-*`, `size-*` or `p*-*` class (a `[…]:` or `*:` child variant is fine): their `size` variant does it (`sm`, `lg`, `icon`).
- `[review]` `ui` One primary color, blue, for the main action of a screen; `destructive`, `success` and `warning` only mean a status, never decoration.
- `[review]` `ui` Cards hold only repeated items, dialogs and framed tools: no bordered box directly inside another bordered box (a hand-drawn `rounded-lg border` counts like `Card`; the repeated items of a list or board, such as a kanban card in its column or a phone card in a list, are the repeated items), no page section styled as a floating card, and corners from the radius scale. The demo frame on `/style` is a light tinted surface with no border.
- `[review]` `ui` The page title is `<Heading>`, the largest text on the page, and sections, panels, rows and tables use `Heading`'s smaller variants; one font family. Only the two blocks of the next rule draw larger text: `Hero` its heading, `PricingPlans` the pricing page's title and its prices.
- `[review]` `ui` The display sizes belong to the two blocks that use them, `Hero` and `PricingPlans`: a page cannot pick a heading size, and the display classes (`text-display`, `text-display-lg`) appear only in components/shared/hero.tsx and pricing-plans.tsx.
- `[review]` `ui` No gradients, glows, blurred orbs or decorative backgrounds, no row of identical stat cards with big numbers as the page opener (number tiles are `StatTiles`, lane `behaviour`), no all-caps or letter-spaced labels, and no second accent colour.
- `[review]` `ui` A feed row has no card and no grey background.
- `[review]` `ui` Flat: only what floats over the page (dropdown, popover, dialog, sheet, toast) has a shadow; borders separate everything else.
- `[review]` `ui` Hover is a neutral grey (the `accent` token), never blue, and every enabled clickable element has a press state one step darker than its hover colour (`active:` with a `-pressed` token) without moving or resizing; a selected toggle or current sidebar page uses the press colour; a column header that can be clicked always shows the faint up-down arrow.
- `[review]` `ui` Motion is a quick transition on hover, open, close or expand, and nothing moves when the user asks for reduced motion; no looping or decorative animation.
- `[review]` `ui` A page that matches a pattern in docs/design/patterns uses that pattern and copies the structure of the sample or demo it names; a page with no pattern is built from foundations.md and components.md, and the report says so.
- `[review]` `ui` A list of records is a `ResponsiveTable`: its name, status and amount first, horizontal row lines only, one search box above it, sort and filters in the column headers (a column menu; on a phone a "Sort & filter" sheet), one card per row on a phone, and with no records it still shows its header row (or box) with the centred empty state inside. Sort, filters and search live in the URL only. A notification or activity list is one bordered box with line-separated rows: no card per row, no grey row background and no button on a row; clicking an unread row marks it as read.
- `[review]` `ui` A page about one record has a breadcrumb that ends at its list, and the record's name appears once, as the page heading; there is no separate back button.
- `[review]` `ui` A form field is a `FormField`, which decides from the width of its form: the label sits to the left of the input when the form is wide, above it when the form is narrow (a phone, the sign-in column, a narrow dialog); an error gives it a red border and no text under it.
- `[review]` `ui` A status is a `Badge` with a solid fill (`secondary`, `success`, `warning` or `destructive`) and the label the backend sends, starting with a capital letter.
- `[review]` `ui` At desktop and phone width, in English and in Chinese, text stays inside its box and nothing overlaps; long text wraps or truncates, and no page scrolls sideways.
- `[review]` `ui` An existing component (components/ui, components/shared) is used instead of hand-styled markup, and restyled through its variants, not by overriding its colors with `className`.
- `[review]` `ui` Every row of action buttons is the shared `Actions` block (`components/shared`): at the right end of its row with the main button last, and full width and stacked with the main button on top when its container is narrow. `ResponsiveDialogFooter`, `WizardLayout`'s footer, record headers, toolbars, settings forms and the single section buttons use it; a component that is not `Actions` places its own buttons.
- `[review]` `ui` Every changed page is opened with isolated headless Playwright at desktop (1280px) and phone (390px) width, and once in dark mode, compared with the style overview page, and the rules above are checked on temporary screenshots before closing the browser (docs/rules/visual-design.md).
- `[review]` `ui` A list whose records have no detail page puts each row's actions in one three-dot menu (`RowActions` through `ResponsiveTable`'s `rowActions`), a single action too: no other button in a row, the destructive action last and set apart, at the top right of the card on a phone. A list with a detail page has no action column (patterns/list.md).

## Lane `behaviour`: principle 10, Screens: behaviour and text

### 10. Screens follow DESIGN.md and docs/design (continued)

The rules of principle 10 for what a screen does and says. Its introduction is in lane `look`.

- `[review]` `ui` The first screen after login is the dashboard, a working screen, and the layout does the explaining: no secondary labels or descriptions, no description line under any title (page, section, card or dialog), no help text under every field, no sentence that explains what a feature does, no warning box above a button that already asks for confirmation, no slogans, no text about how the screen was built or what was asked for, and nothing shown twice.
- `[review]` `ui` No "Back to …" button next to a breadcrumb that already links back, and a status word starts with a capital letter.
- `[review]` `ui` A dashboard shows only data that arrives from the backend: no placeholders and no made-up numbers. A row of number tiles is `StatTiles` (backend numbers only) on a report page, and on a dashboard when its plan asks for it.
- `[review]` `ui` Explaining text appears only for an unusual state (an error, an empty list, a locked record) or a step the user must take; other details sit behind a Tooltip, a collapsible section or a detail page. A sentence under a title has fixed places only: the `consequence` of a `ConfirmDialog` for something that cannot be undone (what will happen), and the `instruction` of a step the user must take (a wizard step, a two-factor step). `ResponsiveDialog` has no description.
- `[review]` `ui` One rule for where a form lives: creating or editing a record that the user opened from a list is a `ResponsiveDialog` (on a phone it slides up from the bottom at full width); a page whose purpose is the form (sign-in, settings, contact, checkout, a wizard) is that page.
- `[review]` `ui` Every `<Form>` and `<form>` sets `noValidate` (not `noValidate={false}`), so the browser never shows its own message; inputs keep `required`, so that screen readers announce the field.
- `[review]` `ui` Messages are toasts at the top centre: results of actions, network errors, field errors (one toast per error, at most five plus one "And N more errors", for forms and imports alike) and what an `Alert` used to show; no `Alert` on a page. After a failed submit the page or dialog scrolls to the first field with an error and focuses it (no animation under reduced motion).
- `[review]` `ui` Familiar controls: a Select for a list of options, a Checkbox for yes or no, a ToggleGroup for two to seven modes, tabs for views.
- `[review]` `ui` An icon-only `Button` (`size="icon…"`), the three-dot button of `RowActions` too, has a `Tooltip` and `aria-label={t('…')}` or `aria-labelledby`.
- `[review]` `ui` An empty list shows `EmptyState`, a region that failed to load `ErrorState`, data that arrives later `LoadingState`, all three centred inside the table or box that frames the list.
- `[review]` `ui` An action that cannot be undone, a delete or a bulk delete too, asks in a `ConfirmDialog` first.
- `[review]` `ui` Every input has a label. It is visible unless the layout already says what the field is (a search box next to a Search button), and then it is hidden with `sr-only` or `aria-label`, never left out. The submit button is disabled while the form is sending.
- `[review]` `ui` Keyboard focus stays visible, actions are buttons and navigation is links (no clickable `div`), every enabled clickable element shows the pointing-hand cursor and a disabled one the arrow, text is readable against its background, and light and dark mode both work.
- `[review]` `ui` Button labels say what happens ("Save changes", "Delete task", not "Submit" or "OK"), and an error message says how to fix the problem.
- `[review]` `ui` A change to a screen changes only what the plan names: the rest of the page stays as it was, and removing or simplifying is preferred over adding.
- `[review]` `ui` A date field is the shared `DatePicker` (components/shared/date-picker.tsx), which reads and writes `YYYY-MM-DD`: no `type="date"` on `input` or `Input`.
- `[review]` `ui` A chart is the chart block with `chart-1` for one series and `chart-1` to `chart-5` in order with a legend for several, never a hex or status colour; the range of a report and the selected tab live in the URL.
- `[review]` `ui` Ticking rows shows the floating `BulkActionBar` with the count and the actions, "Select all N results" covers the list's own search and filters; an import returns every row error, which the validation toasts show (five plus "And N more errors"), and imports nothing when a row is wrong; an export is the list as the user sees it (patterns/bulk-actions.md, patterns/import-export.md).
- `[review]` `ui` A customer page ships no invented reviews, customer logos, user numbers or stock photos: it shows only what the project supplies, and a picture block stays a neutral placeholder.
- `[review]` `ui` Text a user reads is never written straight into JSX: text children (template literals too) and the `title`, `placeholder`, `aria-label`, `ariaLabel`, `label`, `alt`, `consequence`, `instruction`, `submitLabel`, `footer`, `footerLink`, `emptyMessage`, `searchLabel`, `backLabel`, `more` and `message` props go through `t()` or `tChoice()` from `@/lib/i18n`, in every file outside components/ui and the code Wayfinder generates.
- `[review]` `ui` Every sentence passed to `t()`, `tChoice()`, `msg()`, `__()`, `trans()`, `trans_choice()`, `Lang::get()`, `@lang` or `sentence()` has a non-empty Chinese in `lang/zh_CN.json` (a group key such as `auth.failed` in `lang/zh_CN/`); no entry is unused (the sentences Laravel and Fortify send count as used); the placeholders match; and the zh_CN validation, auth, passwords and pagination groups hold every key of lang/en (`custom.*` excepted).
- `[review]` `ui` Every sentence a user reads is translated (docs/rules/translations.md): dates and numbers go through the current language, what users typed is never translated, and the only English left on a screen in Chinese is user content, which includes the example data a demo receives.
- `[oxlint]` `ui` `t()` and `tChoice()` from `@/lib/i18n` never run at module level: outside every function, also inside a module-level array or object and in a class field, they run before the language is set and keep the English sentence. Mark the sentence with `msg()` and call `t()` where it is shown (`ezdev/no-module-level-t`).

## Machines and hooks: principles 2 and 9

### 2. Layers only depend downwards

- `[PHPStan]` `structure` Enums use no models, policies, actions, http or the request.
- `[PHPStan]` `structure` Models use no policies, actions, http, the request or the Gate.
- `[PHPStan]` `structure` Policies use no actions, http or the request.
- `[PHPStan]` `structure` Actions use no HTTP classes or helpers (request, response, session, Inertia, `request()`, `auth()`, `abort()`, `redirect()`).
- `[PHPStan]` `structure` Resources and FormRequests call no Actions.
- `[PHPStan]` `structure` Notifications use no actions, http or the request.
- `[PHPStan]` `structure` Controllers use no `DB` facade.
- `[PHPStan]` `structure` Integrations are used only by Actions; the HTTP client (`Http::`) only inside app/Integrations.
- `[PHPStan]` `structure` The app never uses seeders, test code, or a factory outside its model's `HasFactory` annotation.

### 9. Work follows the steps

docs/workflow.md has the steps.

- `[hook]` `process` At each stop the finish check (scripts/hooks/finish-check.php, its four checks in its header) sends back a plan missing for changed code, a check that did not pass on this code, a finished plan whose lanes are not done, and a protected change the approved plan does not name.
- `[hook]` `process` A reviewer subagent runs only Bash commands that read (search, viewing files, git commands that read): scripts/hooks/reviewer-commands.php refuses a write, a redirection, a substitution and the network of `reviewer-opus` and `reviewer-haiku` before it runs and writes the refusal to reviewer-refusals.jsonl in the state folder. The builder's own commands and `explorer` are not looked at.
- `[Structure]` `process` No check is silenced: no `@phpstan-ignore`, `oxlint-disable`, `eslint-disable`, `@ts-ignore` or `@ts-expect-error`, no skipped or unfinished test (Pest fails on them), and no `ignoreErrors`, `excludePaths` or baseline in phpstan.neon.
- `[Structure]` `process` The checks stay as set: PHPStan's level, paths and includes and every layer rule of principle 2 with its folders, the Ezdev oxlint rule and `import/no-cycle` on with only generated code ignored, strict TypeScript, the three Pest suites, the steps of `composer check`, no other lint config file, no `script-shell` in .npmrc, and every check proved on a broken fixture (tests/Unit/Lint/SkeletonChecksTest.php). Changing them is a protected change.
