# Customer self-service area

Use for the pages a signed-in customer opens to see their own orders and details. It is not the admin app: it has the public layout, not the sidebar.

## Layout, top to bottom

1. The public layout (landing.md): the top bar shows "Dashboard" or the account link in place of "Log in" and "Get started".
2. `AccountNav` (`components/shared`): the account's pages as one row of links under the top bar: Overview, Orders, Profile. The current page uses the selected colour (`bg-accent-pressed`). On a phone the row scrolls sideways inside its own strip.
3. The page under it:
   - Overview: recent orders (a short `ResponsiveTable`) and `QuickLinks` (`components/shared`).
   - Orders: the list pattern (list.md).
   - One order: the detail pattern (detail.md) with "Print" and "Download PDF" (print.md).
   - Profile: the settings pattern (settings.md).

## Rules

- A customer sees only their own records; the Policy decides, as everywhere (.claude/rules/models.md).
- Each page follows its own pattern; this file only says what surrounds them.
- A project that also has staff pages keeps them in the admin app. The two never share a sidebar.

## Phone

`AccountNav` becomes the scrolling strip; the lists become cards (list.md).

## Sample and demo

Demo: `resources/js/components/style/customer-area-demo.tsx`, shown on the style overview page.
