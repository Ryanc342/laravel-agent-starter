# Ezdev template: design

The style of every screen, and the index of the design spec. Read this file before you change any page, then only the files the index names for your task. Every value (colour, radius, font, spacing) is defined once, in `resources/css/app.css`; the spec files name tokens and say when to use them, and never repeat a value. How to apply the spec and what the reviewer checks: docs/rules/visual-design.md. A project with its own brand changes the primary colour and the logo and nothing else; replacing the style is the user's decision.

## Who it is for

The template serves two kinds of screen: internal systems that people use every day to enter, find and check records, and pages that customers see. Both use the same style. Desktop and phone matter equally, and every screen holds English and Chinese text.

## Style: clean and bright

- White surfaces, one blue primary colour, generous whitespace. The layout does the explaining: a screen whose purpose is clear from its title, its fields and its buttons needs no extra words.
- Roomy: controls, rows and sections have more space than a dense admin tool. Finding and finishing the task matters more than fitting more on screen.
- Flat: borders separate things; only what floats over the page (dropdown, popover, dialog, sheet) has a shadow.
- Small corners, one font family, a light grey sidebar.
- Motion is subtle and quick, and stops when the user asks for reduced motion.
- Copy is short and direct.
- The appearance follows the system by default; light and dark both work from the same tokens.

## Which file to read

| Task | Read |
| --- | --- |
| Any page | this file |
| Colour, type, language, space, shape, motion, copy, messages | docs/design/foundations.md |
| Choosing a component or a variant | docs/design/components.md |
| A list of records (orders, products, customers, tasks) | docs/design/patterns/list.md |
| One record's own page | docs/design/patterns/detail.md |
| Creating or editing something | docs/design/patterns/form.md |
| An empty list, a region that failed or is loading | docs/design/patterns/states.md |
| The page after login | docs/design/patterns/dashboard.md |
| Account or app settings | docs/design/patterns/settings.md |
| A stream of messages (notifications, activity) | docs/design/patterns/feed.md |
| Login, register and the other sign-in pages | docs/design/patterns/auth.md |
| No permission, page not found, server error | docs/design/patterns/error-page.md |
| See everything in the running app | the style overview page at `/style` (development only) |

A page that matches no pattern is built freely from foundations.md and components.md. Tell the user, who adds its pattern later.

## Do's and Don'ts

Do:

- Open on the working screen: after login the user sees the dashboard, not a welcome page.
- Say what a button does: "Save changes", "Delete task", "Send quote", not "Submit" or "OK".
- Write errors that say how to fix the problem.
- Keep every text inside its box at phone and desktop width, in English and in Chinese; long names wrap or truncate.
- Show explaining text only for an unusual state (an error, an empty list, a locked record) or a step the user must take. Put other details behind a Tooltip, a collapsible section or a detail page.
- Title and nothing under it. The only sentences next to a title are in a confirmation for something that cannot be undone (it says what will happen) and in a step the user must take (the two-factor setup dialog says what to do).
- Show a field error as a toast, and mark the field with a red border.
- Change only the part of a screen the plan names, and keep the rest as it was. Prefer removing or simplifying to adding.

Don't:

- Gradients, glows, blurred orbs or decorative backgrounds.
- A card inside a card, or page sections styled as floating cards.
- A row of identical stat cards with big numbers as the page opener, unless the plan asks for it on a dashboard.
- Marketing-size headlines (only the home and pricing pages may have them), all-caps or letter-spaced labels, or a second accent colour.
- Secondary labels and descriptions the layout already makes clear: a description line under any title (page, section, card or dialog), help text under every field, a sentence that explains what a feature does, a warning box above a button that already asks for confirmation.
- Error text under a field, a status word that starts with a lowercase letter, a card or a grey background on a feed row.
- Slogans, text about how the screen was built or what was asked for, and the same information shown twice.
- Raw colours (`bg-blue-500`, `#3366ff`), arbitrary sizes (`text-[13px]`, `p-[18px]`) or `dark:` colour overrides: use the tokens.
- A "Back to …" button next to a breadcrumb that already links back.
