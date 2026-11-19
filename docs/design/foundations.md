# Foundations

Read when a task needs colour, type, language, space, shape, motion, wording or a message. The values live in `resources/css/app.css`: light values in `:root`, dark values in `.dark`, mapped to Tailwind classes in `@theme`. This file names the tokens and says when to use them. Use the classes (`bg-primary`, `text-muted-foreground`, `rounded-lg`), never raw colours (`bg-blue-500`, `#hex`), arbitrary values in square brackets or `dark:` colour overrides. The spec names tokens and component sizes and never a value: a pixel size or a Tailwind size class does not appear in DESIGN.md or docs/design/ (tests/Structure/DesignDocsTest.php), so a value changes in app.css or in a component's `size` variant only.

## Colour

| Role | Tokens | Use for |
| --- | --- | --- |
| Page and surfaces | `background`, `card`, `popover` | the page, tables and inputs; cards, dropdowns, popovers and dialogs |
| Sidebar | `sidebar`, `sidebar-foreground`, `sidebar-accent`, `sidebar-border` | the app sidebar only (light grey) |
| Text | `foreground`, `muted-foreground` | text; secondary text, captions and empty lines |
| Quiet fills | `secondary`, `muted`, `accent` | a neutral button or badge; hover (`accent` is a neutral grey, never blue) |
| Press | `primary-pressed`, `secondary-pressed`, `destructive-pressed`, `accent-pressed`, `sidebar-accent-pressed` | the colour of a fill while it is pressed, and of a selected toggle or the sidebar's current page; one step darker than that fill's hover colour |
| Brand | `primary`, `primary-foreground` | the main action of a screen, links, the selected item, the unread dot |
| Lines and focus | `border`, `input`, `input-focus`, `ring` | dividers and boxes; input borders; the dark grey border of a field you click into; the keyboard focus ring of buttons, toggles and badges |
| Status | `destructive`, `success`, `warning`, each with its `-foreground` | a status and nothing else: an error or a delete, something done or paid, something that needs attention |
| Scan surface | `qr-background` | behind a QR code only; white in dark mode too, so a phone can scan it |
| Charts | `chart-1` to `chart-5` | chart series: one series is `chart-1`; several take `chart-1` to `chart-5` in that order and show a legend; never a status colour (patterns/report.md) |

- One primary colour, blue, for the one main action of a screen. No other accent colour.
- Everything that carries the brand (focus ring, selected sidebar item, links, `chart-1`) follows `--primary` in app.css, so a project rebrands by changing `--primary` (light and dark) and the logo. The focus ring is the keyboard ring of buttons, toggles and badges; a form field has no ring (see Inputs in components.md).
- Hover is grey, not blue: hovering over anything clickable (outline and ghost buttons, menu and select items, column headers, the sidebar, rows) turns it the `accent` grey (`sidebar-accent` in the sidebar), in light and dark mode.
- Press feedback is the same everywhere: every clickable element has an `active:` state one step darker than its hover colour (every button variant, menu and select items, toggles, column headers, notification rows, quick links, sidebar items); a link's text gets darker. Nothing moves or changes size. A selected toggle and the sidebar's current page use the press colour, so hover and selected stay distinguishable.
- Text on a status fill uses that status's `-foreground` token, so it stays readable in both themes.
- Every screen works in light and dark mode from the same tokens. The default follows the system.

## Type

- One family, `font-sans`: the stack is defined once in app.css (with a CJK fallback) and its web font is loaded by the `fonts` list in vite.config.ts. A project changes the family in those two places only.

| Role | Use |
| --- | --- |
| Page title | `<Heading>`: one per page and the largest text on it, apart from the display text of `Hero` and `PricingPlans` (Display, below) |
| Section title | `<Heading variant="small">` |
| Body | the text size of `components/ui` and of the sample you copy |
| Secondary text | the body size in `text-muted-foreground`; a caption is one step smaller |
| Display | the display tokens `--text-display` and `--text-display-lg` in app.css (semibold; the larger one from the `md` breakpoint up). `Hero` uses them for its heading and `PricingPlans` for its title and its prices (a price takes the smaller one, so a long price still fits its card). Nowhere else |

- The display sizes belong to the two blocks that use them, `Hero` and `PricingPlans`. A page cannot pick a heading size: `Heading` has its variants only, and every other heading is smaller than the page title.
- Numbers that line up in tables or totals use `tabular-nums`. Money, quantities and dates go through `resources/js/lib/format.ts` (`formatMoney`, `formatQuantity`, `formatDate`), never formatted by hand. `formatMoney` takes an amount in minor units (cents) and, when it is not the project's default, a currency code.
- Sentence case for titles, labels and buttons. No all-caps and no letter-spacing.

## English and Chinese

The app speaks two languages, English (`en`) and Simplified Chinese (`zh_CN`). A first visit follows the browser's `Accept-Language` (any Chinese gives `zh_CN`, anything else `en`); the user switches on Settings → Appearance, and a guest with the "EN / 中文" switch at the top right. The rules for writing and translating a sentence are in .claude/rules/translations.md.

- Every sentence a user reads is written in English through `t()` (React) or `__()` (PHP) and has its Chinese in the same commit. Copy rules (short, sentence case, a button says what happens) hold in both languages; check a screen in both.
- Dates, numbers and money go through `lib/format.ts`, which follows the current language; the date picker's month and day names follow it too.
- Give text room: no width, column or button size fitted to one English label. Text wraps or truncates (`truncate`, or a flex child that is allowed to shrink) and never overflows or overlaps. A Chinese label is shorter than an English one and has no spaces, so a layout looks right with either.
- Names, titles and messages from the user's data arrive in either language: show them as they come.

## Space and density

- Roomy. Spacing comes from Tailwind's spacing scale, and the samples show the step each place uses. Page padding and section gaps are set in the layouts and the sample page of each pattern, and the height and padding of a control are set in `components/ui`: copy the classes of the sample, and change a control's size only through its `size` variant, never with `h-*` or `p-*`.
- Sections of a page are separated by space, not by cards, borders or rules.
- Desktop and phone are equal. The phone layout applies below the `md` breakpoint, where the sidebar collapses and slides out from a button. Every screen works from the narrowest phone up and never scrolls sideways as a page. Each pattern says what changes on a phone: a table becomes cards, a form field's label moves above its input when its form is narrow, a dialog slides up as a full-width sheet.

## Shape and depth

- Corners come from the radius scale: `rounded-md` for buttons, inputs and badges; `rounded-lg` for rows, cards, tables and dialogs. Nothing is rounder: no pills, no `rounded-2xl`. Only avatars and status dots are round.
- Flat. Borders separate things, not shadows. Only what floats over the page has a shadow: dropdowns, popovers, dialogs, sheets and toasts. Buttons, inputs, cards and tables have none.

## Motion

Subtle and quick: colour and opacity changes on hover, a short transition when something opens, closes or expands. Nothing bounces, loops or moves for decoration. When the user asks for reduced motion, nothing moves.

## Icons

Icons come from `lucide-react`. An icon next to text takes its size from the component. An icon-only button has a `Tooltip` and an `aria-label`.

## Copy

- Short and direct. Titles, labels and buttons are in sentence case.
- A button says what happens ("Save changes", "Delete task"). An error says how to fix the problem ("Enter a date after today").
- Explaining text appears only for an unusual state or a step the user must take. No description line under a title (page, section, card or dialog). A sentence under a title has fixed places only: the `consequence` of a `ConfirmDialog` for something that cannot be undone (what will happen) and the `instruction` of a step the user must take (what to do).
- A status word starts with a capital letter ("Open", "Done").

## Messages

| What happened | How it shows |
| --- | --- |
| An action finished (saved, deleted, sent) | a toast: `Inertia::flash('toast', ...)` in the controller, shown by the layout |
| A network error | a toast, shown by the layout |
| A message that used to be an `Alert` on the page | a toast |
| A field is wrong | a toast, one per error; the field keeps only its red border and no text under it |
| An action cannot be undone | a confirmation dialog first, with the action named in full ("Delete task") |
| A region could not load | `ErrorState` (patterns/states.md) |

No `Alert` on a page.

- Toasts appear at the top centre. Any burst of errors, from a form or an import, shows at most five toasts plus one "And N more errors" (`useValidationErrorToasts` does the cutting); the toasts stack in sonner's default way.
- After a failed submit the page or dialog scrolls smoothly to the first field with an error and puts the cursor in it. When the user asks for reduced motion it jumps there without the animation.

## Accessibility

Keyboard focus stays visible. Actions are buttons and navigation is links; nothing clickable is a `div`. Text is readable against its background in both themes. Every input has a label, hidden with `sr-only` only when the layout already says what the field is.
