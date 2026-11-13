# Approval flow

Use for a record that someone must approve or reject (a leave request, an expense, a quote). It is a detail page (detail.md) with two more things: the decision buttons and a timeline.

## Layout, top to bottom

1. Header row, as in detail.md: the record's name as `<Heading>`, its status as a `Badge`, and on the right the actions. Approve (`default`) and Reject (`outline`) come first, then the other actions. They show only when the backend's `can.*` flags say the user may decide.
2. The record's facts: a `dl` (detail.md).
3. The timeline under a `<Heading variant="small">` "History": `Timeline` (`components/shared`), one row per event, newest last, each with who did what and when ("Mei submitted · 12 Nov, 09:30"). A rejection shows its reason under the row.

## Actions

- Approve acts at once and a toast says what happened.
- Reject opens a `ResponsiveDialog` titled "Reject request", with one required `Input` for the reason (a `FormField`) and the buttons "Reject request" (`destructive`) and Cancel.
- After a decision the buttons disappear and the status `Badge` changes.

## Phone

The header row wraps: title and badge first, then the buttons. The timeline stays one column.

## Rules

- Who may approve, the allowed status changes and the timeline's events come from the backend. React decides nothing about eligibility.

## Demo

`resources/js/components/style/approval-demo.tsx`, shown on the style overview page.
