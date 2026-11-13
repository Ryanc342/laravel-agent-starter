# Detail page

Use for one record's own page: a task, an order, a customer.

## Layout, top to bottom

1. Header row: the record's name or number as `<Heading>`, its status as a `Badge` next to it (one more `Badge` for a flag such as overdue), its actions on the right. Actions are shown from the record's `can.*` flags.
2. The breadcrumb ends at the list (`Tasks`); the record's name appears once, as the page heading. `Tasks` is a link, because the last crumb is plain text only when it is the page the user is on (`Breadcrumbs`). It is the way back, so there is no back button.
3. The record's facts: a `dl`, each fact a `dt` (label, `text-muted-foreground`) above its `dd` (value). A fact with no value says so in a few words ("No due date").
4. Related records: each group under a `<Heading variant="small">`, as a list without its own header row (list.md).

## Actions

- The main action is the one `default` `Button`; the others are `outline`.
- Edit opens the form dialog (form.md), filled with the record.
- Delete, and anything else that cannot be undone, asks in a confirmation dialog first, with the action named in full ("Delete task") and one sentence saying what will happen. After a delete the user lands on the list and a toast says what happened.

## Phone

The header row wraps: title and badges first, then the actions. Facts stay label above value.

## States

A record that does not exist is a 404 page (error-page.md). An action the user may not do is not shown: the backend sends the `can.*` flags, and the reason when the page explains one.

## Sample and demo

Copy `docs/reference/tasks/resources/js/pages/tasks/show.tsx` and `docs/reference/tasks/resources/js/components/tasks/task-actions.tsx`. Demo: `resources/js/components/style/detail-demo.tsx`.
