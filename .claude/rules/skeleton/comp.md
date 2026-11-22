---
paths:
  - "resources/js/components/**"
---

# COMP: Components

Location: `resources/js/components/: ui (original), shared, <feature>/ (parts: shared/submit-button.tsx, shared/confirm-button.tsx, shared/icon-button.tsx, shared/export-button.tsx and the boxed fields: text-field, password-field, textarea-field, select-field, date-picker)`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Location

- **COMP-1** New components go in components/<feature>/ or components/shared/; the components root holds only the starter kit's layout and general components. `machine` (Structure: FoldersTest, PlacementChecks::componentsRoot())

## Does

- **COMP-2** How the screen looks; shared by every project, not redesigned. `note`
- **COMP-3** Each component's header says which need it serves and what its options do. `note`

## Must

- **COMP-4** Every shared component is shown on the /style page. `machine` (Structure: FoldersTest, PlacementChecks::styleShowsSharedComponents())
- **COMP-5** A boxed field is TextField, PasswordField, TextareaField, SelectField, DatePicker or a search box: its label is required and floats inside the box; there is no placeholder. `part`
- **COMP-6** A button with only an icon is IconButton: its label is required and becomes the tooltip and the name screen readers read. `part`

## Must not

- **COMP-7** Send a form with anything but SubmitButton: no other element says type="submit", and a plain <button> says its type. `machine` (oxlint: ezdev/submit-button (lint/oxlint/ezdev.js))
- **COMP-8** A feature component imports another feature's component; a shared component imports a feature component; a component imports a page; imports form a cycle. `machine` (oxlint: ezdev/component-boundaries and import/no-cycle)
- **COMP-9** The original components (ui) import the app's other components, pages or routes. `machine` (Structure: CodeTest, PlacementChecks::uiImportsNoDomainCode())

## Proof

- **COMP-10** A component or hook that calls useState, useReducer or useEffect, sends a request, or writes storage or cookies has a Vitest file at the same path under tests/js/; a change to its behaviour changes that test. `reviewer`
  - Check: Look at: new and changed components and hooks, and tests/js. Break: useState, useReducer, useEffect or useLayoutEffect, a request, storage or cookies written, or a handler with logic of its own, and no Vitest file at the same path under tests/js/. Not: a component that only renders props or passes a handler on; a test that already covers it. Cite: the component path and the missing test path.

## Also applies here

- **PAGE-3** (from PAGE) An action that cannot be undone uses ConfirmButton: pressing it always opens ConfirmDialog first, which says what will happen, and only the dialog's confirm button sends the request. `part`
- **PAGE-4** (from PAGE) While a request is being sent, the button that sent it is disabled, so it cannot be sent twice: a form's submit button is SubmitButton. `part`
- **PAGE-5** (from PAGE) An action on selected rows acts only on the rows the user selected, and only within the current search and filters (use BulkActionBar and useRowSelection). `part`
- **PAGE-7** (from PAGE) Text on the screen is short: no description under a title; a button says only its verb ("Save", "Delete"); an error says how to fix the problem; titles, labels and buttons are in sentence case. `reviewer`
- **PAGE-10** (from PAGE) Work out a count, total, yes/no decision, list of accepted values or today's date that a screen shows or uses: the frontend does not count, sum, filter, compare dates or read the clock; the backend sends these. Allowed: checking whether a received list is empty, UI state, and formatting a received value with a shared helper. `reviewer`

## Arrows

- WEB>HTTP: Frontend → backend (requests) ([card](./web-to-http.md))
- HTTP>WEB: Backend → pages (data) ([card](./http-to-web.md))
- PAGE>COMP: Pages and layouts → components ([card](./page-to-comp.md))
- COMP>THEME: Components → theme ([card](./comp-to-theme.md))
