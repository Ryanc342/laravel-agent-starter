# Frontend (React + Inertia) and the data contract

Applies to `resources/js/**` and `app/Http/Resources/**`.

## React displays; the backend decides

React never calculates business results. Anything that is a rule (price, total, status, deadline, permission, "can this button be pressed?") is calculated in PHP and arrives as a prop.

```tsx
// Wrong: business rule calculated in React
const isOverdue = task.status === 'open' && new Date(task.due_on) < new Date();
{user.id === task.user_id && <Button>Edit</Button>}

// Correct: the backend sent the answers
{task.is_overdue && <Badge variant="destructive">Overdue</Badge>}
{task.can.complete && <Button>Complete</Button>}
```

Pure display logic is fine in React: formatting a date, toggling a dialog, filtering a list the user is looking at. "Today" and the current year come from the backend, as a shared prop with the date in the app's timezone (used by `MonthCalendar`, the `DatePicker`'s today mark and the public footer's year); React never reads the clock, so `new Date()` and `Date.now()` do not appear in `resources/js` outside `lib/month-grid.ts` and `lib/date-value.ts` (laying out a month, the grid and the previous and next month, is display) and `lib/format.ts` reading a value it was given. Any date calculation that decides something (overdue, due, eligible) stays in the backend. Every amount, quantity and date on screen goes through `resources/js/lib/format.ts`: `formatMoney(cents)`, `formatQuantity(value)`, `formatDate('2026-11-04')`. The template ships it with `Intl` and its test in `tests/js/lib/format.test.ts`; change the locale and currency at its top for the project, and add a function there when a new kind of value needs one. Never format numbers or dates anywhere else, and never take them apart by hand.

oxlint (the `lint` block in vite.config.ts, Ezdev's rule in lint/oxlint/ezdev.js) checks only the component boundaries below and circular imports. The reviewers check the rest of principle 4 in docs/golden-principles.md (lane `frontend`): no `fetch` or `axios`, no other UI library, a TypeScript type with the same fields as its Resource, no model in a page prop, and whether React decides a business rule (permission checks on `auth.user`, status, role or type comparisons under any name, date comparisons and subtraction, money arithmetic and summing, numbers formatted by hand with `toFixed()` or `padStart()`, hard-coded URLs). The types stay hand-written until a tagged Wayfinder release generates page prop types (the untagged dev-next branch, tried on 2026-11-01, typed wrapped and paginated Resources wrongly). Never rename fields to slip past a rule.

Combining stored fields into a status is a business rule, not formatting: `status === 'done' && outcome === 'partial' ? 'warning' : status` belongs in the Model, and the Resource sends the result. Dividing integer cents by 100 inside a currency formatter is display; totals, discounts, taxes and eligibility are not.

## The Resource is the contract

- Every model sent to React goes through a JsonResource in `app/Http/Resources/`. Never pass a raw model or a hand-built array from a controller.
- `toArray()` returns one literal array that lists each field by name, and `can` is a literal array too. Never return the whole model (`parent::toArray()`, `attributesToArray()`) or spread an arbitrary array. A relation goes through its own Resource (`LineResource::collection($this->whenLoaded('lines'))`).
- The Resource includes the derived flags (`is_overdue`) and permissions (`can: { update, delete, complete }`).
- Each flag and each `can.*` only calls existing owners (Policy abilities, model and enum methods); it never writes a condition. Each `can.*` has a consistency test (docs/rules/tests.md). A row may cost a few small queries this way; a list page shows at most 100 rows, so that is accepted, not debt. Never work a flag out a second way to save a query; a page measured slow goes into the report.
- A `can.*` flag already includes the business state: the button shows exactly when pressing it succeeds. When the page explains why something is not possible ("still in use"), the Resource sends that text; React never works out the reason from `!can.*` or from a mix of flags.
- Data every page needs (the signed-in user, the unread count) is shared in `app/Http/Middleware/HandleInertiaRequests.php`. Data only one widget needs, such as the bell's newest notifications, is an `Inertia::optional()` shared prop that the widget loads with `router.reload({ only: [...] })`.
- Collections arrive wrapped in `data`: pass `TaskResource::collection($tasks)->response($request)->getData(true)` as the prop, read it as `tasks.data`, and type it with `ResourceCollection<T>` from `@/types/resource`.
- Each Resource has a matching TypeScript type in `resources/js/types/{model}.ts` with the same field names. When you change one, change the other in the same edit.

## Forms and routes

- Use Inertia's `<Form>` component with Wayfinder: `<Form {...TaskController.store.form()}>`.
- Never hard-code URLs. Import routes from `@/routes/...` or controller actions from `@/actions/...`.
- Show server errors through the shared `useValidationErrorToasts` hook (`resources/js/hooks/use-validation-error-toasts.ts`, registered once by the Toaster) and `FormField`: one toast per error, a red border on the field (`FormField`), and the first field with an error scrolled into view and focused (a jump, not an animation, under reduced motion). A form never draws error text under a field. Do not duplicate server validation in React beyond simple HTML attributes (`required`, `maxLength`, `min`, `max`, `step`). Every form sets `noValidate`, so the browser never shows its own message in the browser's language: an empty required field reaches the server, whose message comes back in the app's language. `required` stays on inputs so screen readers still announce it. Any burst of errors shows at most five toasts plus one "And N more errors"; `useValidationErrorToasts` does the cutting, and no request cuts its own list. Keeping submit disabled until the input is complete (every digit of a code, a name that is not blank) is display, not validation.
- Sort, filter and search of a list are read by the list's FormRequest from the query string (`search`, `sort`, `direction`, `status[]`, `due_from`, `due_to` and the like), allowed values only; anything else is ignored. The model's scopes apply them, the page links keep the query string, and React only puts the choices into the URL through Inertia (a change requests page 1 and keeps `per_page`). The options of a tick list arrive from the backend as `{ value, label }` pairs.
- Select options come from the list the backend sends, as `{ value, label }` pairs built by one model or enum method (for example `Supplier::options()` returning the active suppliers' ids and names) with one query for the whole list. Never send a full Resource per option, and never run a query per option. Display preferences kept in the browser (the appearance setting) are not business options.

## Files and boundaries

- Page: `resources/js/pages/{domain}/{page}.tsx` (matches `Inertia::render('{domain}/{page}')`).
- Domain components: `resources/js/components/{domain}/`. Components used by several domains: `resources/js/components/shared/`. The starter kit's layout and general components (app-shell, heading, input-error) stay at the components root. tests/Structure/FoldersTest.php checks these folders, and that resources/js holds only `.ts` and `.tsx` files.
- A domain's components never import another domain's components, and nothing imports a page. oxlint enforces this (`ezdev/component-boundaries`, and `import/no-cycle` for circular imports).
- Before creating a component, hook or page section, look for an existing one in the shared folders (`components`, `components/ui`, `components/shared`, `hooks`) **and in the other pages** (`grep -rn` in `resources/js/pages` for the same fields, table headers or labels).
- A UI block that appears on a second page becomes one shared component, and every page uses it. Example: `docs/reference/tasks/resources/js/components/tasks/task-fields.tsx`, used by the create and the edit form.
- Split a page by job, not by size: when a page holds several parts (a list, a form, a summary), each part becomes a component in `components/{domain}/`. A file over 300 lines is a signal to check this.
- Use the shadcn components in `components/ui`; do not install another UI library, also not one that comes in through CSS . components/ui is shadcn's code: it imports no app code.
- Every sentence a user reads goes through `t()` or `tChoice()` from `resources/js/lib/i18n.ts`, never written straight into JSX (docs/rules/translations.md). What users typed is shown as it is.
- How a page looks comes from DESIGN.md and the files in docs/design/ that it names; follow docs/rules/visual-design.md.

## Breadcrumbs and layout

Set breadcrumbs with `Page.layout = { breadcrumbs: [...] }` like `pages/dashboard/index.tsx`. A page about one record ends them at its list, and the record's name appears once, as the page heading: `ShowTask.layout = { breadcrumbs: [{ title: 'Tasks', href: index() }] }` (`docs/reference/tasks/resources/js/pages/tasks/show.tsx`). The breadcrumb is the way back: no separate back button. Show flash messages with `Inertia::flash('toast', [...])` in the controller; the layout already displays them.
