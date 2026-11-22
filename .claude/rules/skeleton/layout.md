---
paths:
  - "resources/js/layouts/**"
---

# LAYOUT: Layouts

Location: `resources/js/layouts/`

This card applies to every file in its location. Every rule here applies in full; the tag says who guarantees it.

## Does

- **LAYOUT-1** The frame around pages, such as the sidebar and the top bar. The template gives a few as starting points; a project may replace them or make new ones. `note`
- **LAYOUT-2** The /style page, which opens only locally, shows every shared component and the demo of every kind of page; it is the only design reference. `note`

## Must

- **LAYOUT-3** A layout reads only keys that HandleInertiaRequests::share() sends on every request (not Inertia::optional ones), plus the layout props a page sets through Page.layout. `reviewer`
  - Check: Look at: usePage().props in layouts. Break: a key share() does not return on every request (a page's own prop, an optional prop). Not: name, signedIn, auth, notificationSummary, today, perPageOptions, sidebarOpen, Inertia's own keys; breadcrumbs through Page.layout. Cite: the line and the key.

## Also applies here

- **PAGE-9** (from PAGE) Use anything but layout in a page's or layout's className (including one passed to a component): only display, flex and grid, gap, padding, margin, width and height, position, overflow, order, visibility and text alignment are allowed; everything else, and any style prop, is looks. `reviewer`
- **PAGE-10** (from PAGE) Work out a count, total, yes/no decision, list of accepted values or today's date that a screen shows or uses: the frontend does not count, sum, filter, compare dates or read the clock; the backend sends these. Allowed: checking whether a received list is empty, UI state, and formatting a received value with a shared helper. `reviewer`

## Arrows

- HTTP>WEB: Backend → pages (data) ([card](./http-to-web.md))
- PAGE>COMP: Pages and layouts → components ([card](./page-to-comp.md))
