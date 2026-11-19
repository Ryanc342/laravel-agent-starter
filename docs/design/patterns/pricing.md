# Pricing page

Use for a page that sets plans side by side so a visitor can choose one.

## Layout, top to bottom

1. The public layout (landing.md).
2. The page title, which `PricingPlans` draws in the display size (the one page title that is not a `Heading`), and the billing choice: a `ToggleGroup` with "Monthly" and "Yearly". The choice lives in the URL (`?billing=yearly`; monthly is the default and has no query), so a link shares it.
3. `PricingPlans` (`components/shared`): the plan cards side by side, equal width. Each card holds the plan's name (`Heading` small), its price in the display size (foundations.md) with the period beside it ("/ month"), a list of features with a check icon each, and one `Button`. The recommended plan has a `primary` border and a "Recommended" `Badge`, and its button is `default`; the others are `outline`. Only one plan is recommended.
4. A link to the help page (`TextLink`, "Questions about plans?") under the cards.

## Rules

- Prices, plan names, features and the yearly saving come from the backend. React only shows the period the toggle picks; it never calculates a total, a discount or a tax.
- Money is an amount in minor units and a currency, shown through `formatMoney` (`lib/format.ts`, foundations.md); never formatted by hand.
- `PricingPlans` owns the display classes: it draws the page title in both display sizes and the prices in the smaller one, so that a price like "RM 1,990.00" fits a card. A page, and the pricing demo, never use a display class themselves.
- A plan's button names what happens ("Choose Team", "Contact sales"), not "Submit".

## Phone

The cards stack, the recommended plan first. The toggle stays at the top.

## Sample and demo

Demo: `resources/js/components/style/pricing-demo.tsx`, shown on the style overview page.
