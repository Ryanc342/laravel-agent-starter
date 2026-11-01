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

Pure display logic is fine in React: formatting a date, toggling a dialog, filtering a list the user is looking at. Date and money formatting for display goes in `resources/js/lib/format.ts`.

oxlint (the `lint` block in vite.config.ts, Ezdev's rules in lint/oxlint/ezdev.js) fails on permission checks (`auth.user`), status, role and type comparisons, date comparisons and subtraction, money arithmetic and summing `reduce`, hard-coded URLs, `fetch` and `axios`, and imports of other UI libraries. tests/Structure/ResourceTypesTest.php fails on a TypeScript type whose fields differ from its Resource. PHPStan fails when a page prop holds a model. If a rule matches code that is really display logic, ask the user to adjust the rule; never rename fields to slip past it.

Combining stored fields into a status is a business rule, not formatting: `status === 'done' && outcome === 'partial' ? 'warning' : status` belongs in the Model, and the Resource sends the result. Dividing integer cents by 100 inside a currency formatter is display; totals, discounts, taxes and eligibility are not.

## The Resource is the contract

- Every model sent to React goes through a JsonResource in `app/Http/Resources/`. Never pass a raw model or a hand-built array from a controller.
- `toArray()` lists each field by name. Never return the whole model (`parent::toArray()`, `attributesToArray()`) or spread an arbitrary array. PHPStan rejects the first two in Resources.
- The Resource includes the derived flags (`is_overdue`) and permissions (`can: { update, delete, complete }`).
- Each flag and each `can.*` only calls existing owners (Policy abilities, model and enum methods); it never writes a condition. Each `can.*` has a consistency test (docs/rules/tests.md).
- Collections arrive wrapped in `data`: pass `TaskResource::collection($tasks)->response($request)->getData(true)` as the prop, read it as `tasks.data`, and type it with `ResourceCollection<T>` from `@/types/resource`.
- Each Resource has a matching TypeScript type in `resources/js/types/{model}.ts` with the same field names. When you change one, change the other in the same edit.

## Forms and routes

- Use Inertia's `<Form>` component with Wayfinder: `<Form {...TaskController.store.form()}>`.
- Never hard-code URLs. Import routes from `@/routes/...` or controller actions from `@/actions/...`.
- Show server errors with `<InputError message={errors.field} />`. Do not duplicate server validation in React beyond simple HTML attributes (`required`, `maxLength`).

## Files and boundaries

- Page: `resources/js/pages/{domain}/{page}.tsx` (matches `Inertia::render('{domain}/{page}')`).
- Domain components: `resources/js/components/{domain}/`. Components used by several domains: `resources/js/components/shared/`.
- A domain's components never import another domain's components, and nothing imports a page. oxlint enforces this (`ezdev/component-boundaries`, and `import/no-cycle` for circular imports).
- Before creating a component, hook or page section, look for an existing one in the shared folders (`components`, `components/ui`, `components/shared`, `hooks`) **and in the other pages** (`grep -rn` in `resources/js/pages` for the same fields, table headers or labels).
- A UI block that appears on a second page becomes one shared component, and every page uses it. Example: `docs/reference/tasks/resources/js/components/tasks/task-fields.tsx`, used by the create and the edit form.
- Split a page by job, not by size: when a page holds several parts (a list, a form, a summary), each part becomes a component in `components/{domain}/`. A file over 300 lines is a signal to check this.
- Use the shadcn components in `components/ui`; do not install another UI library.
- If a `DESIGN.md` exists in the project root, also follow docs/rules/visual-design.md.

## Breadcrumbs and layout

Set breadcrumbs with `Page.layout = { breadcrumbs: [...] }` like `pages/dashboard.tsx`. Show flash messages with `Inertia::flash('toast', [...])` in the controller; the layout already displays them.
