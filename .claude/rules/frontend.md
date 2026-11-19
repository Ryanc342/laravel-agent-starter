---
paths:
  - "resources/js/**"
  - "app/Http/Resources/**"
  - "app/Http/Requests/**"
  - "app/Http/Middleware/HandleInertiaRequests.php"
---

# Frontend (React + Inertia) and the data contract

A rule names its check in its tag, with its lane and level, and says why in one sentence (docs/golden-principles.md); text without a tag explains how to follow them. How a page looks: .claude/rules/visual-design.md. Text a user reads: .claude/rules/translations.md.

## React displays; the backend decides

React never calculates business results. Anything that is a rule (price, total, status, deadline, permission, "can this button be pressed?") is calculated in PHP and arrives as a prop. Pure display logic is fine in React: formatting a date, toggling a dialog. Never rename fields to slip past a rule.

React shows what the backend sent (`is_overdue`, `can.complete`) and never works it out from fields: docs/reference/tasks/resources/js/components/tasks/task-actions.tsx shows it.

- `[review:frontend:1]` React does not check permissions (comparisons with `auth.user`): a button is shown from its `can.*` flag. Why: a permission worked out in React differs from the Policy that decides.
- `[review:frontend:2]` React does not compare the business statuses, roles and types the backend sends, under any name: it shows the `is_*` flags and labels, and combining stored fields into a status belongs in the Model, whose result the Resource sends. Why: a status worked out in React is a second copy of the model's rule. Not a break: the page's own state (the selected tab, the view mode, an open dialog).
- `[review:frontend:1]` React does not compare or subtract dates to decide anything, and "today" and the current year come from the backend as a shared prop (the date in the app's timezone), never from `new Date()` or `Date.now()`. Why: the browser's clock and timezone differ from the app's, so the answer is wrong for some users. Not a break: formatting in resources/js/lib/format.ts, and laying out a month (the grid, the previous and next month) in `lib/month-grid.ts` and `lib/date-value.ts`.
- `[review:frontend:1]` React does not calculate money or sum lists: totals, discounts and taxes come from the Resource. Why: money worked out in the browser disagrees with what the backend stores and charges. Not a break: dividing integer cents by 100 inside a currency formatter in resources/js/lib/format.ts.
- `[review:frontend:2]` React decides no other business rule either (eligibility, deadlines, limits): the Resource sends the result. Why: a rule decided in React is a second copy of the backend's.
- `[review:frontend:2]` React formats every amount, quantity and date with `Intl` in resources/js/lib/format.ts (`formatMoney(cents)`, `formatQuantity(value)`, `formatDate('2026-11-04')`), never with `toFixed()` or `padStart()` or by taking them apart by hand. Why: a value formatted in two ways looks different on two pages and misses the language.

The template ships format.ts with its test in `tests/js/lib/format.test.ts`; change the locale and currency at its top for the project, and add a function there when a new kind of value needs one.

## The Resource is the contract

- `[review:frontend:1]` Every model reaches React through a JsonResource: the props of `Inertia::render()`, `inertia()`, `->with()`, `Inertia::share()`, the shared props and `response()->json()` never hold a model, a model collection or a paginator of models, also not as `->toArray()`, inside an array, a closure or `Inertia::defer()`. Why: a raw model sends every column, hidden ones included, to the browser. Send `{Model}Resource::make($model)->resolve($request)` for one record and `{Model}Resource::collection($models)->response($request)->getData(true)` for a list.
- `[review:backend:1]` A Resource lists each field by name, and a relation goes through its own Resource (`LineResource::collection($this->whenLoaded('lines'))`): no `parent::toArray()`, `$this->resource->toArray()`, `getAttributes()`, `(array) $this->resource` or `get_object_vars($this->resource)` in a Resource, and no `toArray()`, `attributesToArray()`, `getAttributes()`, `getOriginal()`, `toJson()` or `jsonSerialize()` on a model anywhere in app/. Why: a model dumped whole sends every column, and a new column reaches the browser unnoticed.
- `[review:frontend:2]` Each Resource has a TypeScript type in resources/js/types/ with the same fields and the same `can.*` keys (an extra field only if it is optional), exported right after the comment "Matches app/Http/Resources/{Model}Resource.php", and both change in the same edit; the Resource's `toArray()` returns one literal array with fixed keys (no spread), and `can` is a literal array. Why: a type that differs from its Resource lets React read a field that never arrives.
- `[review:frontend:1]` A `can.*` flag includes the business state, so a button shows exactly when pressing it succeeds, and a reason shown for a refusal ("still in use") comes from the Resource, never worked out from `!can.*` or a mix of flags. Why: otherwise the page offers what the backend refuses, or gives the wrong reason.

Never pass a raw model or a hand-built array from a controller. The Resource includes the derived flags (`is_overdue`) and permissions (`can: { update, delete, complete }`). Each flag and each `can.*` only calls existing owners (Policy abilities, model and enum methods) and never writes a condition, and each `can.*` has a consistency test (.claude/rules/tests.md). A row may cost a few small queries this way; a list page shows at most 100 rows, so that is accepted, not debt. Never work a flag out a second way to save a query; a page measured slow goes into the report.

Data every page needs (the signed-in user, the unread count) is shared in `app/Http/Middleware/HandleInertiaRequests.php`. Data only one widget needs, such as the bell's newest notifications, is an `Inertia::optional()` shared prop that the widget loads with `router.reload({ only: [...] })`. Collections arrive wrapped in `data`: read them as `tasks.data` and type them with `ResourceCollection<T>` from `@/types/resource`. The types stay hand-written until a tagged Wayfinder release generates page prop types (the untagged dev-next branch, tried on 2026-11-01, typed wrapped and paginated Resources wrongly).

## Requests, forms and options

- `[review:frontend:2]` No `fetch` or `axios` (also `window.fetch`, `window.axios`, `axios/…` and `import('axios')`), in components/ui too: requests go through Inertia, with a Wayfinder route from @/routes or @/actions and `router` or `useForm`. Why: a request around Inertia skips its errors, its flash messages and its validation toasts.
- `[review:frontend:2]` No hard-coded URL of the app's own routes: they come from Wayfinder (`@/routes`, `@/actions`). Why: a hard-coded URL breaks unnoticed when its route changes. Not a break: an external link (a help page, a `mailto:`).
- `[review:frontend:2]` Validation lives in the FormRequest; React adds only simple HTML attributes (`required`, `maxLength`, `min`, `max`, `step`). Why: a second set of checks in React disagrees with the server's, whose message comes back in the app's language. Not a break: keeping submit disabled until the input is complete (every digit of a code, a name that is not blank).
- `[review:frontend:2]` Select options come from the list the backend sends, as `{ value, label }` pairs from one model or enum method (`Supplier::options()`) with one query for the whole list, never a full Resource or a query per option. Why: options built in React, or in several ways, disagree with what the backend accepts. Not a break: display preferences kept in the browser (the appearance setting).

Use Inertia's `<Form>` component with Wayfinder: `<Form {...TaskController.store.form()}>`. Server errors are shown by the shared `useValidationErrorToasts` hook (`resources/js/hooks/use-validation-error-toasts.ts`, registered once by the Toaster) and `FormField` (how they look: .claude/rules/visual-design.md); the hook does the cutting, so no request cuts its own list.

Sort, filter and search of a list are read by the list's FormRequest from the query string (`search`, `sort`, `direction`, `status[]`, `due_from`, `due_to` and the like), allowed values only; anything else is ignored. The model's scopes apply them, the page links keep the query string, and React only puts the choices into the URL through Inertia (a change requests page 1 and keeps `per_page`). The options of a tick list arrive from the backend as `{ value, label }` pairs.

## Files and boundaries

- `[Structure]` Pages live in `resources/js/pages/{domain}/` (`{page}.tsx` matches `Inertia::render('{domain}/{page}')`); the components root holds only the starter kit's layout and general components (app-shell, heading, input-error), so new components go in `components/{domain}/` or `components/shared/`; components/ui is shadcn's code: it never imports the app's other components, pages, actions, routes or Wayfinder (the shared helpers, such as `@/lib/utils` and `@/lib/i18n`, are allowed).
- `[oxlint]` Domain components never import another domain's components; components/shared never imports domain components; nothing imports a page; no circular imports (`ezdev/component-boundaries`, `import/no-cycle`).
- `[review:frontend:3]` No component kit besides components/ui (shadcn): no other set of ready-made buttons, inputs and dialogs (antd, bootstrap, react-bootstrap, primereact, semantic-ui-react, flowbite, `@headlessui/react`, `@mui`, `@chakra-ui`, `@mantine`, `@nextui-org`, `@heroui`, `@ant-design` and the like). Why: a second kit gives a second look and a second way to build every control. Not a break: a single-purpose library (a rich-text editor, drag and drop) that an agreed decision of the plan adding it names.
- `[review:frontend:3]` No component kit that comes in through CSS: resources/css `@import`s and `@plugin`s only tailwindcss and tw-animate-css, and package.json has no CSS UI kit (daisyui, bootstrap, bulma, flowbite, preline and the like). Why: a CSS kit restyles the shared components from outside.

Before creating a component, hook or page section, look for an existing one in the shared folders (`components`, `components/ui`, `components/shared`, `hooks`) and in the other pages (`grep -rn` in `resources/js/pages` for the same fields, table headers or labels); a UI block that appears on a second page is one shared component (rule of .claude/rules/app.md on copied blocks), as `docs/reference/tasks/resources/js/components/tasks/task-fields.tsx` is for the create and the edit form. Split a page by job, not by size: when a page holds several parts (a list, a form, a summary), each part becomes a component in `components/{domain}/`.

Set breadcrumbs with `Page.layout = { breadcrumbs: [...] }` like `pages/dashboard/index.tsx`; a page about one record ends them at its list (`ShowTask.layout = { breadcrumbs: [{ title: 'Tasks', href: index() }] }`, `docs/reference/tasks/resources/js/pages/tasks/show.tsx`).
