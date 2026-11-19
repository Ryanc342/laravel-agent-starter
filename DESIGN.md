# Ezdev template: design

The style of every screen, and the index of the design spec. Read this file before you change any page, then only the files the index names for your task. Every value (colour, radius, font, spacing) is defined once, in `resources/css/app.css`; the spec files name tokens and say when to use them, and never repeat a value. How to apply the spec and what the reviewer checks: docs/rules/visual-design.md. A project with its own brand changes the primary colour and the logo and nothing else; replacing the style is the user's decision.

## Who it is for

The template serves two kinds of screen: internal systems that people use every day to enter, find and check records, and pages that customers see. Both use the same style. Desktop and phone matter equally, and every screen holds English and Chinese text.

## Style: clean and bright

- White surfaces, one blue primary colour, generous whitespace. The layout does the explaining: a screen whose purpose is clear from its title, its fields and its buttons needs no extra words.
- Roomy: controls, rows and sections have more space than a dense admin tool. Finding and finishing the task matters more than fitting more on screen.
- Flat: borders separate things; only what floats over the page (dropdown, popover, dialog, sheet, toast) has a shadow.
- Small corners, one font family, a light grey sidebar.
- Motion is subtle and quick, and stops when the user asks for reduced motion.
- Everything the user can click shows the pointing-hand cursor; a disabled control keeps the arrow. It turns grey on hover and one step darker while pressed (never blue, nothing moves).
- Copy is short and direct.
- The appearance follows the system by default; light and dark both work from the same tokens.

## Which file to read

| Task | Read |
| --- | --- |
| Any page | this file |
| Colour, type, language, space, shape, motion, copy, messages | docs/design/foundations.md |
| Choosing a component or a variant | docs/design/components.md |
| A list of records (orders, products, customers, tasks), with its column menus for sort, filter and search | docs/design/patterns/list.md |
| One record's own page | docs/design/patterns/detail.md |
| Creating or editing something | docs/design/patterns/form.md |
| Searching more than one kind of record, and the results page | docs/design/patterns/search-results.md |
| A long task in steps | docs/design/patterns/wizard.md |
| A record someone approves or rejects, with its history | docs/design/patterns/approval.md |
| Many records one after another, the list beside the selected one | docs/design/patterns/list-detail.md |
| One record with several tabs | docs/design/patterns/detail.md (Detail with tabs) |
| Ticking many rows and acting on them | docs/design/patterns/bulk-actions.md |
| Moving records in or out as a CSV file | docs/design/patterns/import-export.md |
| Something to print or save as a PDF (invoice, receipt) | docs/design/patterns/print.md |
| Numbers over time, charts and a date range | docs/design/patterns/report.md |
| Records by date, in a month | docs/design/patterns/calendar.md |
| Records in status columns | docs/design/patterns/kanban.md |
| An empty list, a region that failed or is loading (drawn inside the table or box, centred) | docs/design/patterns/states.md |
| The page after login | docs/design/patterns/dashboard.md |
| Account or app settings | docs/design/patterns/settings.md |
| A stream of messages (notifications, activity) | docs/design/patterns/feed.md |
| Login, register and the other sign-in pages | docs/design/patterns/auth.md |
| The home page, or any page that introduces the product (hero, features, one call to action) | docs/design/patterns/landing.md |
| Plans and prices side by side | docs/design/patterns/pricing.md |
| One product or service of the business | docs/design/patterns/product.md |
| The pages a signed-in customer opens for their own orders and details | docs/design/patterns/customer-area.md |
| Turning a cart into an order | docs/design/patterns/checkout.md |
| Answers to the questions customers ask most | docs/design/patterns/help.md |
| A visitor writes to the business | docs/design/patterns/contact.md |
| No permission, page not found, server error | docs/design/patterns/error-page.md |
| See everything in the running app | the style overview page at `/style` (development only). It has no backend: what a backend would answer (a status, a validation result, an import's errors) comes with the page's example data through props |

A page that matches no pattern is built freely from foundations.md and components.md, and the report says so.

## Do's and Don'ts

Do:

- Open on the working screen: after login the user sees the dashboard, not a welcome page.
- Say what a button does: "Save changes", "Delete task", "Send quote", not "Submit" or "OK".
- Write errors that say how to fix the problem.
- Keep every text inside its box at phone and desktop width, in English and in Chinese; long names wrap or truncate.
- Show explaining text only for an unusual state (an error, an empty list, a locked record) or a step the user must take. Put other details behind a Tooltip, a collapsible section or a detail page.
- Title and nothing under it. A sentence under a title has fixed places only: the `consequence` of a `ConfirmDialog` for something that cannot be undone (it says what will happen) and the `instruction` of a step the user must take (a wizard step, a two-factor step).
- Show a field error as a toast, and mark the field with a red border.
- Change only the part of a screen the plan names, and keep the rest as it was. Prefer removing or simplifying to adding.

Don't:

- Gradients, glows, blurred orbs or decorative backgrounds.
- A bordered box directly inside another bordered box, or page sections styled as floating cards. A hand-drawn `rounded-lg border` counts like `Card`: a mock top bar in a demo is a full-width strip with a line under it, not a box. The repeated items of a list or board are the repeated items (a kanban card in its column, a card of a list on a phone). The demo frame on `/style` is a light tinted surface with no border.
- A row of identical stat cards with big numbers as the page opener. A row of number tiles is the shared `StatTiles`: a report page has it (patterns/report.md), a dashboard has it when its plan asks for it (patterns/dashboard.md).
- Marketing-size headlines (the display sizes belong to `Hero` and `PricingPlans`; a page cannot pick a heading size), all-caps or letter-spaced labels, or a second accent colour.
- Secondary labels and descriptions the layout already makes clear: a description line under any title (page, section, card or dialog), help text under every field, a sentence that explains what a feature does, a warning box above a button that already asks for confirmation.
- Error text under a field, a status word that starts with a lowercase letter, a card or a grey background on a feed row.
- Slogans, text about how the screen was built or what was asked for, and the same information shown twice.
- Raw colours (`bg-blue-500`, `#3366ff`), arbitrary values in a class (a size in square brackets) or `dark:` colour overrides: use the tokens. A component is resized through its `size` variant, never with `h-*` or `p-*`.
- A "Back to …" button next to a breadcrumb that already links back.
