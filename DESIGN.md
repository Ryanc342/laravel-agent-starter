---
version: alpha
name: Ezdev work tool
description: A quiet, clear interface for people who use the system every day to enter, find and check records.
colors:
  background: "oklch(1 0 0)"
  foreground: "oklch(0.145 0 0)"
  primary: "oklch(0.205 0 0)"
  primary-foreground: "oklch(0.985 0 0)"
  muted: "oklch(0.97 0 0)"
  muted-foreground: "oklch(0.556 0 0)"
  border: "oklch(0.922 0 0)"
  destructive: "oklch(0.577 0.245 27.325)"
  success: "oklch(0.527 0.154 150.069)"
  success-foreground: "oklch(0.985 0 0)"
  warning: "oklch(0.769 0.188 70.08)"
  warning-foreground: "oklch(0.205 0 0)"
typography:
  page-title:
    fontFamily: Instrument Sans
    fontSize: 20px
    fontWeight: 600
    lineHeight: 28px
  section-title:
    fontFamily: Instrument Sans
    fontSize: 16px
    fontWeight: 500
    lineHeight: 24px
  body:
    fontFamily: Instrument Sans
    fontSize: 14px
    fontWeight: 400
    lineHeight: 20px
  label:
    fontFamily: Instrument Sans
    fontSize: 14px
    fontWeight: 500
    lineHeight: 20px
  caption:
    fontFamily: Instrument Sans
    fontSize: 12px
    fontWeight: 500
    lineHeight: 16px
rounded:
  sm: 4px
  md: 6px
  lg: 8px
spacing:
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.primary-foreground}"
    typography: "{typography.label}"
    rounded: "{rounded.md}"
    height: 36px
    padding: 16px
  input:
    backgroundColor: "{colors.background}"
    textColor: "{colors.foreground}"
    typography: "{typography.body}"
    rounded: "{rounded.md}"
    height: 36px
  list-row:
    backgroundColor: "{colors.background}"
    textColor: "{colors.foreground}"
    rounded: "{rounded.lg}"
    padding: 16px
  badge-success:
    backgroundColor: "{colors.success}"
    textColor: "{colors.success-foreground}"
    typography: "{typography.caption}"
    rounded: "{rounded.md}"
  badge-warning:
    backgroundColor: "{colors.warning}"
    textColor: "{colors.warning-foreground}"
    typography: "{typography.caption}"
    rounded: "{rounded.md}"
---

# Ezdev work tool

How every screen of this app looks. The values above are the theme in `resources/css/app.css` (light values in `:root`, dark values in `.dark`); change both together. How to apply this file: docs/rules/visual-design.md. A project with its own brand replaces this file, keeping the section order; that is the user's decision.

## Overview

People open this app to get work done: enter an order, find a customer, check what is overdue. The interface stays quiet and out of the way: neutral surfaces, one dark primary color for the main action, compact type, and dense but readable lists. The layout does the explaining: a screen whose purpose is clear from its title, its fields and its buttons needs no extra words. Finding and finishing the task matters more than looking impressive. The settings and notifications pages (`resources/js/pages/settings`, `resources/js/pages/notifications`) and the reference feature (`docs/reference/tasks`) show the look.

## Colors

- Greys for surfaces, borders and secondary text; `foreground` for text; `primary` (near black, near white in dark mode) for the one main action of a screen. Other buttons are `outline`, `secondary` or `ghost`.
- `destructive`, `success` and `warning` mean a status and nothing else: an error or a delete, something done or paid, something that needs attention. Badge has a variant for each.
- No other accent color. Charts use `chart-1` to `chart-5`.
- Every screen works in light and dark mode; both come from the same tokens.

## Typography

- One family, Instrument Sans, loaded by the `fonts` list in vite.config.ts.
- Body text is 14px (`text-sm`). The page title is `<Heading>` (20px, semibold); a section inside a page is `<Heading variant="small">`. Nothing inside the app is larger.
- Numbers that line up in tables or totals use `tabular-nums`. Money, quantities and dates are shown through `resources/js/lib/format.ts` (`formatMoney`, `formatQuantity`, `formatDate`), which also sets the locale and currency.
- Sentence case for titles, labels and buttons.

## Layout

- Top bar of every page: the sidebar toggle and breadcrumbs on the left, the notification bell on the right. The bell opens a card with the newest notifications.
- Page: `flex flex-col gap-6 p-4`. First a header row, `flex flex-wrap items-start justify-between gap-4`, with `<Heading>` on the left and the page's actions on the right. Then filters, then the content, then `<Pagination>`.
- List page: a list of records (orders, products, customers, tasks) is always a `Table` from `components/ui/table` inside a `rounded-lg border` box, like `docs/reference/tasks/resources/js/components/tasks/task-table.tsx`. Columns go in this order: the record's name or number (a link to its page), its status, its amount, then the rest, so a phone shows the important columns before the table scrolls. A feed of messages, such as notifications, is rows instead (`rounded-lg border p-4`, the `list-row` component above). An empty list shows one short line in `text-muted-foreground`.
- Form: `grid max-w-xl gap-4`; each field is a label, the input and `<InputError>` under it; the buttons come last, the main one first. A label is hidden (`sr-only`) only when the layout already says what the field is, such as a search box next to a Search button; it is never left out.
- Detail page: the record's name as the heading, its status as a Badge next to it, its actions in the header row. Its breadcrumb ends at the record (`Tasks › Buy milk`, `Purchase orders › PO-2026-0002 › Edit`), built from the page's data as in `docs/reference/tasks/resources/js/pages/tasks/show.tsx`; the breadcrumb is the way back, so there is no separate back button.
- Spacing comes from the scale (`gap-1`, `gap-2`, `gap-4`, `gap-6`, `gap-8`). Every screen works from a 390px phone up; a wide table scrolls inside its own container, never the page.

## Elevation & Depth

Flat. Borders separate things, not shadows. Only what floats over the page has a shadow: dropdowns, popovers, dialogs and sheets, as the components in `components/ui` already do.

## Shapes

Corners come from the radius scale: `rounded-md` for buttons, inputs and badges, `rounded-lg` for rows, cards and dialogs. Nothing is rounder: no pill buttons, no `rounded-2xl`.

## Components

Use what `resources/js/components/ui` (shadcn) and `components/shared` already have before writing markup:

| Need | Use |
| --- | --- |
| A list of records | `Table` (`components/ui/table`) |
| An action | `Button` (one `default` per area, the rest `outline`, `secondary` or `ghost`) |
| A status | `Badge` (`secondary`, `success`, `warning`, `destructive`) |
| Confirm something that cannot be undone | `Dialog` with Cancel and the action named in full ("Delete task") |
| A side panel | `Sheet` |
| A small card that opens from a button (the notification bell) | `Popover` |
| A message on the page | `Alert` |
| A toast after an action | the flash toast (`Inertia::flash('toast', ...)`, shown by the layout) |
| Data that arrives later | `Skeleton` |
| Yes or no | `Checkbox` |
| Two to seven modes | `ToggleGroup` |
| A list of options | `Select` |
| An icon-only button | `Tooltip` and an `aria-label` |
| Paging | `Pagination` from `components/shared` |

Icons come from `lucide-react`. A shadcn component that is not installed yet (Table, Tabs) is added with the shadcn CLI; ask the user first if it brings a new package.

## Do's and Don'ts

Do:

- Open on the working screen: after login the user sees their work, not a welcome page.
- Say what a button does: "Save changes", "Delete task", "Send quote", not "Submit" or "OK".
- Write errors that say how to fix the problem.
- Keep every text inside its box at phone and desktop width; long names wrap or truncate.
- Show explaining text only for an unusual state (an error, an empty list, a locked record) or a step the user must take. Put other details behind a Tooltip, a collapsible section or a detail page.
- Change only the part of a screen the plan names, and keep the rest as it was. Prefer removing or simplifying to adding.

Don't:

- Gradients, glows, blurred orbs or decorative backgrounds.
- A card inside a card, or page sections styled as floating cards.
- A row of identical stat cards with big numbers as the page opener, unless the page is a dashboard the plan asked for.
- Marketing-size headlines, all-caps or letter-spaced labels, or a second accent color.
- Secondary labels and descriptions the layout already makes clear: a description under a heading that repeats it ("Tasks: your to-do list"), help text under every field, a sentence that explains what a feature does.
- Slogans, text about how the screen was built or what was asked for, and the same information shown twice.
- Raw colors (`bg-blue-500`, `#3366ff`), arbitrary sizes (`text-[13px]`, `p-[18px]`) or `dark:` color overrides: use the tokens.
- A "Back to …" button next to a breadcrumb that already links back.
