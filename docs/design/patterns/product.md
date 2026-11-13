# Product or service page

Use for one product or service of the business: what it is, what it offers, the key facts and one next step.

## Layout, top to bottom

1. The public layout (landing.md).
2. The title (`<Heading>`, at most 20px) and one line that sums the product up.
3. Sections that alternate: text on one side (a `Heading` small and a short paragraph) and a picture block on the other, then the other way round. The picture block is a neutral `bg-muted` rectangle with `rounded-lg` where the project's own picture goes; the starter ships no stock photo.
4. The key facts as a `dl` (what is included, delivery time, the price): `dt` in `text-muted-foreground`, `dd` the value. A price goes through `formatMoney`.
5. One call to action: a `default` `Button` that names the next step ("Request a quote", "Order now").

## Rules

- Everything about the product (text, facts, price) comes from the backend; React shows it.
- One main button on the page. A second way in is an `outline` button or a link.
- No headline above 20px: the display size belongs to the home and pricing pages only.

## Phone

The sections stack, the picture block under its text. The key facts keep label and value on one line when they fit, and wrap otherwise.

## Sample and demo

Demo: `resources/js/components/style/product-demo.tsx`, shown on the style overview page.
