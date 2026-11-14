# Order / checkout

Use for turning a cart into an order. It is a wizard (wizard.md) on its own page, with the order summary beside the steps.

## Layout, top to bottom

1. The public layout (landing.md) and the title (`<Heading>`, "Checkout").
2. `WizardLayout` (`components/shared`) with three steps: Cart, Details, Review. The step list is on the left.
3. The current step's fields:
   - Cart: `LineItems` (`components/shared`), one row per item with its name, unit price, a quantity `Input` (named `items[0][quantity]`, `items[1][quantity]`, …) and the line total. A row can be removed with an icon `Button`.
   - Details: the customer's name, email and address as `FormField`s.
   - Review: what was entered, read only, and the place for the payment provider's own element.
4. `OrderSummary` (`components/shared`): the subtotal, tax and total as a `dl`, the total in `tabular-nums` and bold. On a computer it sits on the right; on a phone it sits above the buttons.
5. The buttons last, as `Actions` (components.md): "Back" (`outline`, hidden on step 1) and "Next" (`default`). The last step's button names the result ("Place order").

## Rules

- Subtotal, tax, shipping and total come from the backend; React only shows them. A changed quantity is sent to the server, which answers with the new summary.
- Money is an amount in minor units and a currency, shown through `formatMoney`.
- Payment belongs to the project's payment provider. The page shows where the provider's own element goes and never has a card number, expiry or CVC field of its own.
- Errors are toasts and red borders (foundations.md, Messages); a line item's error scrolls to and focuses its quantity input.
- Nothing is ordered until the last step.

## Phone

The step list is one line ("Step 2 of 3 · Details"); each line item becomes a stack of its name, price, quantity and total; the summary and the buttons stack at full width.

## Sample and demo

Demo: `resources/js/components/style/checkout-demo.tsx`, shown on the style overview page. A failed step fires the event Inertia fires, so the real toasts and the focus on `items[0][quantity]` show.
