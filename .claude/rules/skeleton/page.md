---
paths:
  - "resources/js/pages/**"
---

# PAGE: Pages

Location: `resources/js/pages/<feature>/`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Location

- **PAGE-1** Pages live in pages/<feature>/, with names that match the page the backend renders. `machine` (Structure: FoldersTest, PlacementChecks::pagesInDomainFolders())

## Does

- **PAGE-2** One screen: which components it shows and how they are arranged, as the plan decides. `note`

## Must

- **PAGE-3** An action that cannot be undone uses ConfirmButton: pressing it always opens ConfirmDialog first, which says what will happen, and only the dialog's confirm button sends the request. `part`
- **PAGE-4** While a request is being sent, the button that sent it is disabled, so it cannot be sent twice: a form's submit button is SubmitButton. `part`
- **PAGE-5** An action on selected rows acts only on the rows the user selected, and only within the current search and filters (use BulkActionBar and useRowSelection). `part`
- **PAGE-6** On a computer and a phone, in light and dark: no sideways scrolling, no page errors, no accessibility issue at any level, contrast included. (A browser test visits every page.) `machine` (Browser test: tests/Browser/PagesTest.php)
- **PAGE-7** Text on the screen is short: no description under a title; a button says only its verb ("Save", "Delete"); an error says how to fix the problem; titles, labels and buttons are in sentence case. `reviewer`
  - Check: Look at: text in JSX, labels, titles, toasts and the messages rules() and refusal() return. Break: a description line under a title; a button that says more than its verb ("Save changes", "Delete task"); an error that only says what is wrong; Title Case or all caps. Not: the consequence of a ConfirmButton and the instruction of a step the user must take; names and text from the data. Cite: the line.
- **PAGE-8** The screen matches the screenshot baseline the user approved; a change of looks is approved by a plan with new screenshots. (Text that leaves its box and overlaps are caught here too.) `test` (Browser test: the screenshot baselines in tests/Browser)

## Must not

- **PAGE-9** Use anything but layout in a page's or layout's className (including one passed to a component): only display, flex and grid, gap, padding, margin, width and height, position, overflow, order, visibility and text alignment are allowed; everything else, and any style prop, is looks. `reviewer`
  - Check: Look at: className, cn() and style in pages and layouts, and className passed to a component. Break: bg-*, text colour or size, font-*, border*, divide-*, rounded*, shadow*, animate-*, a style prop. Not: layout classes (flex, grid, gap, p, m, w, h, max-w, min-w-0, items, justify, overflow, hidden, md:block, col-span, text-left and text-center); a variant or size prop. Cite: the path, line and class.
- **PAGE-10** Work out a count, total, yes/no decision, list of accepted values or today's date that a screen shows or uses: the frontend does not count, sum, filter, compare dates or read the clock; the backend sends these. Allowed: checking whether a received list is empty, UI state, and formatting a received value with a shared helper. `reviewer`
  - Check: Look at: pages, layouts, components, lib and hooks using props. Break: counts, sums or filters records to decide something; compares dates or reads the clock (new Date(), Date.now()); decides yes or no from fields; keeps its own list of accepted values. Not: reading backend fields and flags (can.*, is_unread, meta.total, today, perPageOptions); .map to render; UI state. Cite: the line and the backend field to read instead.

## Also applies here

- **COMP-5** (from COMP) A boxed field is TextField, PasswordField, TextareaField, SelectField, DatePicker or a search box: its label is required and floats inside the box; there is no placeholder. `part`
- **COMP-6** (from COMP) A button with only an icon is IconButton: its label is required and becomes the tooltip and the name screen readers read. `part`
- **COMP-7** (from COMP) Send a form with anything but SubmitButton: no other element says type="submit", and a plain <button> says its type. `machine` (oxlint: ezdev/submit-button (lint/oxlint/ezdev.js))

## Arrows

- WEB>HTTP: Frontend → backend (requests) ([card](./web-to-http.md))
- HTTP>WEB: Backend → pages (data) ([card](./http-to-web.md))
- PAGE>COMP: Pages and layouts → components ([card](./page-to-comp.md))
