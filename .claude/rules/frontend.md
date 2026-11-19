---
paths:
  - "resources/js/**"
  - "app/Http/Resources/**"
  - "app/Http/Requests/**"
  - "app/Http/Middleware/HandleInertiaRequests.php"
---

# Frontend (React + Inertia) and the data contract

A rule names its check in its tag (docs/golden-principles.md); text without a tag explains how to follow them. How a page looks: .claude/rules/visual-design.md. Text a user reads: .claude/rules/translations.md.

## React displays; the backend decides

React never calculates business results. Anything that is a rule (price, total, status, deadline, permission, "can this button be pressed?") is calculated in PHP and arrives as a prop. Pure display logic is fine in React: formatting a date, toggling a dialog, filtering a list the user is looking at. Never rename fields to slip past a rule.

```tsx
// Wrong: business rule calculated in React
const isOverdue = task.status === 'open' && new Date(task.due_on) < new Date();
{user.id === task.user_id && <Button>Edit</Button>}

// Correct: the backend sent the answers
{task.is_overdue && <Badge variant="destructive">Overdue</Badge>}
{task.can.complete && <Button>Complete</Button>}
```

- `[review:frontend]` React does not check permissions (comparisons with `auth.user`). Buttons are shown from `can.*`.
- `[review:frontend]` React does not compare statuses, roles or types, under any name. It shows the `is_*` flags and labels. Combining stored fields into a status (`status === 'done' && outcome === 'partial' ? 'warning' : status`) belongs in the Model, and the Resource sends the result.
- `[review:frontend]` React does not compare or subtract dates to decide anything. Display formatting goes in resources/js/lib/format.ts. "Today" and the current year come from the backend as a shared prop (the date in the app's timezone), never from `new Date()` or `Date.now()`; laying out a month (the grid, the previous and next month) is display and lives only in `lib/month-grid.ts` and `lib/date-value.ts`.
- `[review:frontend]` React does not calculate money or sum lists. Dividing integer cents by 100 inside a currency formatter in resources/js/lib/format.ts is display; totals, discounts and taxes come from the Resource.
- `[review:frontend]` React decides no other business rule either (eligibility, deadlines, limits): the Resource sends the result.
- `[review:input]` React formats every amount, quantity and date with `Intl` in resources/js/lib/format.ts (`formatMoney(cents)`, `formatQuantity(value)`, `formatDate('2026-11-04')`), never with `toFixed()` or `padStart()`, and never takes them apart by hand.

The template ships format.ts with its test in `tests/js/lib/format.test.ts`; change the locale and currency at its top for the project, and add a function there when a new kind of value needs one.

## The Resource is the contract

- `[review:frontend]` Every model reaches React through a JsonResource: the props of `Inertia::render()`, `inertia()`, `->with()`, `Inertia::share()`, the shared props and `response()->json()` never hold a model, a model collection or a paginator of models, also not as `->toArray()`, inside an array, a closure or `Inertia::defer()` and the like. Send `{Model}Resource::make($model)->resolve($request)` for one record and `{Model}Resource::collection($models)->response($request)->getData(true)` for a list.
- `[review:business]` A Resource lists each field by name: `parent::toArray()`, `$this->resource->toArray()`, `getAttributes()` and the like, `(array) $this->resource`, `get_object_vars($this->resource)` and a relation sent without its own Resource (`$this->whenLoaded('lines')` alone; wrap it in `LineResource::collection(…)` or `LineResource::make(…)`) are rejected inside Resources, and `toArray()`, `attributesToArray()`, `getAttributes()`, `getOriginal()`, `toJson()` and `jsonSerialize()` on a model are rejected everywhere in app/.
- `[review:frontend]` Each Resource has a TypeScript type in resources/js/types/ with the same fields and the same `can.*` keys (an extra field only if it is optional), exported right after the comment "Matches app/Http/Resources/{Model}Resource.php"; the Resource's `toArray()` returns one literal array with fixed keys (no spread), and `can` is a literal array. When you change one, change the other in the same edit.
- `[review:frontend]` A `can.*` flag includes the business state, so a button shows exactly when pressing it succeeds. A reason shown for a refusal ("still in use") comes from the Resource, never worked out from `!can.*` or a mix of flags.

Never pass a raw model or a hand-built array from a controller. The Resource includes the derived flags (`is_overdue`) and permissions (`can: { update, delete, complete }`). Each flag and each `can.*` only calls existing owners (Policy abilities, model and enum methods) and never writes a condition, and each `can.*` has a consistency test (.claude/rules/tests.md). A row may cost a few small queries this way; a list page shows at most 100 rows, so that is accepted, not debt. Never work a flag out a second way to save a query; a page measured slow goes into the report.

Data every page needs (the signed-in user, the unread count) is shared in `app/Http/Middleware/HandleInertiaRequests.php`. Data only one widget needs, such as the bell's newest notifications, is an `Inertia::optional()` shared prop that the widget loads with `router.reload({ only: [...] })`. Collections arrive wrapped in `data`: read them as `tasks.data` and type them with `ResourceCollection<T>` from `@/types/resource`. The types stay hand-written until a tagged Wayfinder release generates page prop types (the untagged dev-next branch, tried on 2026-11-01, typed wrapped and paginated Resources wrongly).

## Requests, forms and options

- `[review:frontend]` No `fetch` or `axios` (also `window.fetch`, `window.axios`, `axios/…` and `import('axios')`), in components/ui too: requests go through Inertia, with a Wayfinder route from @/routes or @/actions and `router` or `useForm`.
- `[review:frontend]` No hard-coded URLs: routes and controller actions come from Wayfinder (`@/routes`, `@/actions`).
- `[review:frontend]` Validation lives in the FormRequest; React adds only simple HTML attributes (`required`, `maxLength`, `min`, `max`, `step`), so the server's message comes back in the app's language (`noValidate`: .claude/rules/visual-design.md). Keeping submit disabled until the input is complete (every digit of a code, a name that is not blank) is display.
- `[review:frontend]` Select options come from the list the backend sends, as `{ value, label }` pairs from one model or enum method (`Supplier::options()`) with one query for the whole list, never a full Resource or a query per option. Display preferences kept in the browser (the appearance setting) are not business options.

Use Inertia's `<Form>` component with Wayfinder: `<Form {...TaskController.store.form()}>`. Server errors are shown by the shared `useValidationErrorToasts` hook (`resources/js/hooks/use-validation-error-toasts.ts`, registered once by the Toaster) and `FormField` (how they look: .claude/rules/visual-design.md); the hook does the cutting, so no request cuts its own list.

Sort, filter and search of a list are read by the list's FormRequest from the query string (`search`, `sort`, `direction`, `status[]`, `due_from`, `due_to` and the like), allowed values only; anything else is ignored. The model's scopes apply them, the page links keep the query string, and React only puts the choices into the URL through Inertia (a change requests page 1 and keeps `per_page`). The options of a tick list arrive from the backend as `{ value, label }` pairs.

## Files and boundaries

- `[Structure]` Pages live in `resources/js/pages/{domain}/` (`{page}.tsx` matches `Inertia::render('{domain}/{page}')`); the components root holds only the starter kit's layout and general components (app-shell, heading, input-error), so new components go in `components/{domain}/` or `components/shared/`; components/ui is shadcn's code: it never imports the app's other components, pages, actions, routes or Wayfinder (the shared helpers, such as `@/lib/utils` and `@/lib/i18n`, are allowed).
- `[oxlint]` Domain components never import another domain's components; components/shared never imports domain components; nothing imports a page; no circular imports (`ezdev/component-boundaries`, `import/no-cycle`).
- `[review:frontend]` No UI library besides components/ui (shadcn): importing another UI library in React code (antd, bootstrap, react-bootstrap, primereact, semantic-ui-react, flowbite, `@headlessui/react`, `@mui`, `@chakra-ui`, `@mantine`, `@nextui-org`, `@heroui`, `@ant-design` and the like) is rejected.
- `[review:frontend]` No UI library that comes in through CSS only: resources/css `@import`s and `@plugin`s only tailwindcss and tw-animate-css, and no dependency section of package.json has a CSS UI kit (daisyui, bootstrap, bulma, flowbite, preline and the like).

Before creating a component, hook or page section, look for an existing one in the shared folders (`components`, `components/ui`, `components/shared`, `hooks`) and in the other pages (`grep -rn` in `resources/js/pages` for the same fields, table headers or labels); a UI block that appears on a second page is one shared component (rule of .claude/rules/app.md on copied blocks), as `docs/reference/tasks/resources/js/components/tasks/task-fields.tsx` is for the create and the edit form. Split a page by job, not by size: when a page holds several parts (a list, a form, a summary), each part becomes a component in `components/{domain}/`.

Set breadcrumbs with `Page.layout = { breadcrumbs: [...] }` like `pages/dashboard/index.tsx`; a page about one record ends them at its list (`ShowTask.layout = { breadcrumbs: [{ title: 'Tasks', href: index() }] }`, `docs/reference/tasks/resources/js/pages/tasks/show.tsx`).
