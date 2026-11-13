# Frontend design v2, batch 3: the customer pages, a new home page, and the rest of the tech debt

## Purpose
Give AI agents a pattern and a demo for the seven customer-facing pages (home / landing, pricing, product or service page, customer self-service area, order / checkout, help / FAQ, contact form), rebuild the starter's home page in the new style, and close every tech-debt row that can be closed now, including Chinese text in PDFs. The user runs this plan overnight and reviews it in the morning.

## Agreed decisions
From the user, 2026-11-14 (in chat, two questions): this plan covers batch 3 and the tech debt; Claude sets batch 3's design details from the user's earlier answers (listed with their source) and the user reviews the result in the morning, asking for changes in a feedback round; the PDF uses the full Chinese font with a higher PHP memory limit.

Earlier answers this batch builds on: Q23 the customer pages use the same style as the app; Q24 the seven patterns; Q37 larger headings only on the home / landing and pricing pages, everywhere else at most 20px; Q38 customer pages use a top navigation bar; Q39 `/` becomes a home page in the new style in this batch; Q40 the logo stays Laravel's; Q29 and Q30 the design holds English and Chinese, English by default; Q2 computer and phone matter equally; Q12 flat, only floating layers have a shadow; Q17 labels left of inputs (above on a phone); Q76 a multi-step flow is its own page with the steps on the left; Q95 a /style demo takes what a backend would answer from its example data.

Defaults set by Claude for the user's morning review:
1. Public layout (`layouts/public-layout.tsx`): a top bar with the logo on the left, the page links (Product, Pricing, Help, Contact) next to it, and on the right "Log in" and "Get started" (primary), or "Dashboard" when signed in. On a phone the links and buttons move into a `Sheet` opened by a menu button (like Q26). A footer with one row of links and the copyright line. No shadow on the bar.
2. Larger headings (Q37) are one token pair in `resources/css/app.css` (a display size for phone and computer), used only by the home / landing hero and the pricing page title and prices.
3. Home / landing: a hero (heading, one sentence, two buttons), a grid of three to six features (icon, title, one line), one call-to-action band, the footer. No testimonials, customer logos or numbers: a starter must not ship invented reviews or brands.
4. Pricing: plan cards side by side (stacked on a phone), a monthly / yearly `ToggleGroup` whose choice lives in the URL (`?billing=yearly`), one recommended plan with a primary border and a "Recommended" badge, the price in the display size, a list of features with check icons, one button per plan, and a link to the help page.
5. Product or service page: title and one-line summary, then sections that alternate text and a neutral picture block on a computer and stack on a phone (no stock photos), key facts as a list, one call to action.
6. Customer self-service area: signed-in customer pages use the public layout with an account navigation (overview, orders, profile), not the admin sidebar. Overview shows recent orders and quick links; orders use the list pattern; an order uses the detail pattern with "Print" and "Download PDF" (print.md); profile uses the settings pattern.
7. Order / checkout: its own page with the steps on the left (Q76): Cart, Details, Review. The cart edits line items (quantity per item, fields named `items[0][quantity]`); an order summary sits on the right on a computer and above the buttons on a phone. Payment belongs to the project's payment provider: the demo shows where the provider's own element goes and never has a card number field.
8. Help / FAQ: a search box at the top that narrows the questions, questions grouped by topic, each question a `Collapsible` row in a bordered, line-separated box, and a "Still need help? Contact us" link at the end.
9. Contact form: name, email, subject and message in `FormField` rows (labels on the left, above on a phone), one submit button, a success toast, field errors as toasts (Q41). The demo takes its validation answer from its example data (Q95).
10. Real or demo (as Q69 did for batch 2): the new home page `/` is real (no data); the other six patterns are /style demos fed through props (Q33). The home page keeps the Inertia component name `welcome/index` and the route name `home`, so the existing tests that assert them (DashboardTest, ExampleTest, AppearanceTest and others) do not change.
11. Money: a shared `formatMoney` takes an amount in minor units (cents) and a currency and formats it with `Intl.NumberFormat`; pricing, checkout and the order demo use it. An amount range filter is typed in major units (12.50) and the backend converts to minor units; `patterns/list.md` says so. This closes the amount-unit tech-debt row.
12. No new package: the FAQ uses `Collapsible` (`@radix-ui/react-collapsible`, installed), the billing period `ToggleGroup` (installed), the phone menu `Sheet` (installed).

Tech debt closed here:
13. The welcome page row: decision 10's rebuild.
14. The array-name row: `use-validation-error-toasts.ts` finds the field for an error key with dots (`items.0.quantity`) by its bracket name (`items[0][quantity]`) too; the checkout demo's line items use it. + Vitest.
15. The amount-unit row: decision 11.
16. The Chinese PDF font row, as the user chose: the full Noto Sans SC Regular (400) and Bold (700), cut as static TrueType from the official variable font with fontTools in a temporary environment outside the project (upstream has no static TrueType; this is how the batch 2 feedback run made them, about 10.6 MB each), in `resources/fonts/` with `OFL.txt`; `config/laravel-pdf.php` sets the DOMPDF `chroot`, `font_dir` and `font_cache` (`storage/fonts`, kept with a tracked `.gitignore`); the reference PDF view uses the font through `@font-face` for all its text; `phpunit.xml` sets `memory_limit` to 256M (one render peaks at 119 to 129 MB); `docs/design/patterns/print.md` and the README's deployment notes say a server that makes PDFs needs `memory_limit` of at least 256M. If the fonts cannot be made, record it and leave this row open; do not lower the font to a subset.
17. The kanban row: `KanbanBoard` passes dnd-kit `accessibility.announcements` and `screenReaderInstructions` that name the card's title and the column's status. + Vitest.

Working overnight (the user is asleep): when something is unclear, take the most reasonable reading that fits the decisions above, write it in the Decision Log, and continue. Stop and leave the plan open only for: a package not in this plan, an existing test assertion that would have to change, or a protected file this plan does not name.

## Milestones
- [x] M1 Spec: DESIGN.md index rows; new `docs/design/patterns/`: `landing.md`, `pricing.md`, `product.md`, `customer-area.md`, `checkout.md`, `help.md`, `contact.md`; `foundations.md` (the display size, decision 2), `components.md` (the public layout and every new shared block), `patterns/list.md` (decision 11), `patterns/print.md` (decision 16); principle 10 in `docs/golden-principles.md` for a new `[review]` rule where one is needed (decision 3's "no invented reviews, logos or numbers"). + DocsTest.
- [x] M2 Public layout and display size: decisions 1 and 2. + Vitest (signed in and out, the phone menu).
- [x] M3 Home page: `/` rebuilt with the public layout as decisions 3 and 10. + Pest (signed out sees Log in and Get started, signed in sees Dashboard) and Vitest; the existing tests unchanged.
- [x] M4 Shared blocks in `components/shared`, each with Vitest: hero, feature grid, pricing plans with the billing toggle (URL), FAQ list with its search, account navigation, line items, order summary, contact form, `formatMoney`.
- [x] M5 Validation toasts for array names: decision 14.
- [x] M6 /style demos for the seven patterns (decisions 3 to 9), fed through props from `pages/style/index.tsx`, with example data in English and one Chinese example per demo where text length matters (Q29).
- [ ] M7 Chinese PDF font: decision 16. + Pest: the PDF of a task titled 寄发票给供应商 embeds the Noto Sans SC subset; the PDF looked at as an image shows the characters; record the file sizes and the PDF's size; `composer check:reference` passes with the new memory limit.
- [ ] M8 Kanban announcements: decision 17.
- [ ] M9 Docs: README.md and README.zh-CN.md (the patterns, the PDF memory note), ARCHITECTURE.md (the public layout), the CHANGELOG (the unreleased 1.2.0 entry, a batch 3 list), `docs/QUALITY.md`; remove the closed rows from `docs/exec-plans/tech-debt.md`.
- [ ] M10 Checks: full `composer check` and `composer check:reference`; isolated headless Playwright at 1280px and 390px, light and dark: `/` signed in and out, the phone menu, every new /style section (pricing toggle, FAQ open and search, checkout steps with a line-item error that scrolls to `items[0][quantity]`), the Chinese examples (nothing overflows), the task PDF with a Chinese title.
- [ ] M11 Review and report: reviewer rounds (at most 3), fix P0 and P1, a morning report for the user (what was built, every default of this plan the user should look at, what is left), archive this plan.

## Validation
- Per milestone: `composer check:milestone -- <affected test paths>` passes; record the scope and the reason.
- M1: `tests/Structure/DocsTest.php`. M2 to M6 and M8: Vitest for each new block. M3: Pest for `/`. M7: Pest in `docs/reference/tasks/tests/Feature/Tasks` and `composer check:reference`.
- Decision 10: the existing assertions on `welcome/index` and `home` pass unchanged. Decision 12: `package.json` and `composer.json` do not change (M7's fonts are made with fontTools outside the project). Decision 14: an error for `items.0.quantity` focuses the input named `items[0][quantity]`.
- UI: as listed in M10; nothing wider than the screen at 390px; the display size appears only on the home hero and the pricing page.
- Final implementation: full `composer check` and `composer check:reference` pass, repeated after any later code change. No package is added or upgraded.

## Progress
- 2026-11-14 03:05 MYT: The user chose, in chat, batch 3 plus the tech debt with Claude's defaults, and the full Chinese PDF font with a higher memory limit. The batch 2 feedback plan is archived; its PDF findings are in the tech-debt row this plan closes. Plan written; waiting for the user to say start.
- 2026-11-14: The user said start. M1 done: seven pattern files, DESIGN.md index, foundations (display tokens), components (public layout and shared blocks, `Textarea`), list.md (amount unit), print.md (PDF font and memory), principle 10 rule on invented content. 0 tests failed before (docs only, the existing DocsTest checks the new files). Affected scope: tests/Structure/DocsTest.php; `composer check:milestone -- tests/Structure/DocsTest.php` passed (6 tests). The pattern files name their demos by component name, not path, until M6 creates the files.

- 2026-11-14: M2 done: display tokens in app.css; `PublicHeader`, `PublicFooter` (components/shared) and `layouts/public-layout.tsx`. 9 Vitest tests failed before the code (3 files). Scope: the three new test files plus tests/Structure. `composer check:milestone -- tests/js/components/shared/public-header.test.tsx tests/js/components/shared/public-layout.test.tsx tests/js/display-size.test.ts tests/Structure` passed.
- 2026-11-14: M3 done: `/` rebuilt (hero, six features, a closing line) with `PublicLayout` via `app.tsx`; the page keeps `welcome/index` and `home`. 2 Vitest files failed before the code; the 2 new Pest tests (HomeTest) passed at once because the route already behaved that way, they lock it in. Scope: HomeTest, DashboardTest, ExampleTest, SharedDataTest, AppearanceTest, hero, welcome and public-layout Vitest. `composer check:milestone -- <those paths>` passed. Browser look is part of M10.
- 2026-11-14: M4 done: `Textarea`, `PricingPlans`, `FaqList`, `AccountNav`, `LineItems`, `OrderSummary`, `ContactForm` and `formatMoney(minor, currency?)`; `Hero` and `FeatureGrid` came in M3, `PublicHeader` in M2. 5 new Vitest files and 1 new format test failed before the code. Scope: all of tests/js and tests/Structure. `composer check:milestone -- tests/js tests/Structure` passed (53 files, 301 tests).
- 2026-11-14: M5 done: `use-validation-error-toasts.ts` looks for `items[0][quantity]` as well as `items.0.quantity` (any depth). 2 of 4 new Vitest tests failed before the code. Scope: tests/js/hooks/use-validation-error-toasts.test.tsx; `composer check:milestone -- tests/js/hooks/use-validation-error-toasts.test.tsx` passed (16 tests).
- 2026-11-14: M6 done: seven demos (`landing`, `pricing`, `product`, `customer-area`, `checkout`, `help`, `contact`) in `components/style/`, `PublicFrame`, `Variant`, example data and stand-in answers in `customer-examples.ts`, 7 new sections on `/style` each in English and Chinese. 14 Vitest tests in `customer-demos.test.tsx` written with the demos (they passed on the first full run except one mock fix). Scope: tests/js, tests/Structure, tests/Feature/StyleTest.php; `composer check:milestone -- tests/Structure tests/js tests/Feature/StyleTest.php` passed. The pattern files now name their demo by path. Browser look: M10.

## Surprises & Discoveries
- The home page is `Route::inertia('/', 'welcome/index')->name('home')`; DashboardTest asserts the component `welcome/index`, and several tests use `route('home')`.
- Layouts today: `app-layout` (sidebar), `auth-layout`, `settings/layout`; no public layout.
- `foundations.md` already says "Larger headings are allowed only on the home / landing and pricing pages."
- From the batch 2 feedback run: no static TrueType Noto Sans SC upstream; DOMPDF subsetting is on by default in dompdf 3.1.6; a PDF with one Chinese title is about 10 KB; one render peaks at 119 to 129 MB with the full fonts.

## Decision Log
- 2026-11-14: Work continues on the branch `frontend-design-v2-batch-1`; the user merges and releases only after every batch.
- 2026-11-14: Protected files this plan changes, approved when the user says start: DESIGN.md, `docs/design/` (foundations.md, components.md, patterns/list.md, patterns/print.md and the seven new pattern files), `docs/golden-principles.md`, `docs/reference/tasks/` (the PDF view and its test), `phpunit.xml` (memory_limit), and `tests/Structure/FoldersTest.php` only if a new folder needs to be listed there.

- 2026-11-14: The real home page passes no page links to the public layout: Product, Pricing, Help and Contact exist only as /style demos in the starter, and a link to a missing page would be a 404. The layout takes a `links` prop and shows what it is given; docs say a project adds a link when its page exists. The /style demo of the layout shows all four.
- 2026-11-14: `PublicHeader` and `PublicFooter` live in `components/shared` as prop-driven blocks (signed in or not comes in as a prop); `layouts/public-layout.tsx` reads `auth` from the page props and composes them. This lets /style demos draw the bar without a full-page layout.
- 2026-11-14: A `Textarea` is added to `components/ui` (the contact form's message field); it is shadcn markup, not a package.
- 2026-11-14: `Hero` and `FeatureGrid` are built in M3, not M4, because the home page needs them; M4 builds the rest of its list.
- 2026-11-14: `ContactForm` is the fields and the button only and sits inside the page's own Inertia `<Form>`, which gives `errors` and `processing`; the /style demo wraps it in a plain `<form>`. `LineItems` takes errors keyed the way Laravel sends them (`items.0.quantity`) and names its inputs `items[0][quantity]`.
- 2026-11-14: `FormField` still renders the error as `sr-only` text for screen readers; the tests of the new blocks check that, not that the text is absent.
- 2026-11-14: A demo's failed step or send fires the document event `inertia:error` (the one Inertia fires), so the Toaster's real `useValidationErrorToasts` shows the toasts and focuses the first field, including `items[0][quantity]`; no demo-only focus code.
- 2026-11-14: The demos' example data and stand-in server answers (cart totals with 6% tax, errors keyed `items.0.quantity`) live in `components/style/customer-examples.ts` so `pages/style/index.tsx` does not grow by 300 more lines; the page still passes them to the demos through props.

## Outcomes & Retrospective
Not started.
