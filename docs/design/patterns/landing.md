# Home / landing page

Use for the first page a visitor sees (`/`) and any page that introduces the product: a hero, what it offers and one next step. It is the one customer page that is not a working screen; after login the user lands on the dashboard (dashboard.md).

## Layout, top to bottom

1. The public layout (components.md, `PublicLayout`): the top bar and the footer. The page never draws its own.
2. The hero (`Hero`, `components/shared`): the heading in the display size (foundations.md, Type), one sentence under it, and two buttons: the main one (`default`, "Get started") and one `outline` ("Log in", or the second way in). Left-aligned on a computer, with room above and below; no picture and no background.
3. The feature grid (`FeatureGrid`, `components/shared`): three to six features, each an icon, a title (`Heading` small) and one line. Three columns on a computer, one on a phone. No card around a feature.
4. One call-to-action band: a single line and one `default` button, set apart by space and a line above it, not by a coloured background.
5. The footer (part of the public layout).

## Rules

- The text comes from the project. The starter's own page says what the starter is and nothing more: no testimonials, no customer logos, no numbers about users or uptime, no stock photos (.claude/rules/visual-design.md, Behaviour).
- `Hero` owns the display classes, and its heading is the only display text on this page; every other heading is the page title or smaller.
- A signed-in visitor sees "Dashboard" in the top bar instead of "Log in" and "Get started", and the hero's main button goes to the dashboard.
- Copy is short and direct, in sentence case, with no slogan.

## Phone

One column. The hero's buttons stack at full width; the feature grid is one column; the top bar's links and buttons sit in the menu `Sheet` (components.md).

## Sample and demo

Copy `resources/js/pages/welcome/index.tsx` and `resources/js/layouts/public-layout.tsx`. Demo: `resources/js/components/style/landing-demo.tsx`, shown on the style overview page.
