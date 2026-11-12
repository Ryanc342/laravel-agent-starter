# Foundations

Read when a task needs colour, type, language, space, shape, motion, wording or a message. The values live in `resources/css/app.css`: light values in `:root`, dark values in `.dark`, mapped to Tailwind classes in `@theme`. This file names the tokens and says when to use them. Use the classes (`bg-primary`, `text-muted-foreground`, `rounded-lg`, `gap-4`), never raw colours (`bg-blue-500`, `#hex`), arbitrary sizes (`text-[13px]`, `p-[18px]`) or `dark:` colour overrides.

## Colour

| Role | Tokens | Use for |
| --- | --- | --- |
| Page and surfaces | `background`, `card`, `popover` | the page, tables and inputs; cards, dropdowns, popovers and dialogs |
| Sidebar | `sidebar`, `sidebar-foreground`, `sidebar-accent`, `sidebar-border` | the app sidebar only (light grey) |
| Text | `foreground`, `muted-foreground` | text; secondary text, captions and empty lines |
| Quiet fills | `secondary`, `muted`, `accent` | a neutral button or badge; an unread row; hover |
| Brand | `primary`, `primary-foreground` | the main action of a screen, links, the selected item, the unread dot |
| Lines and focus | `border`, `input`, `ring` | dividers and boxes; input borders; the focus ring |
| Status | `destructive`, `success`, `warning`, each with its `-foreground` | a status and nothing else: an error or a delete, something done or paid, something that needs attention |
| Charts | `chart-1` to `chart-5` | chart series |

- One primary colour, blue, for the one main action of a screen. No other accent colour.
- Everything that carries the brand (focus ring, selected sidebar item, links, `chart-1`) follows `--primary` in app.css, so a project rebrands by changing `--primary` (light and dark) and the logo.
- Text on a status fill uses that status's `-foreground` token, so it stays readable in both themes.
- Every screen works in light and dark mode from the same tokens. The default follows the system.

## Type

- One family, `font-sans` (Instrument Sans, with a CJK fallback in the stack), loaded by the `fonts` list in vite.config.ts.

| Role | Use |
| --- | --- |
| Page title | `<Heading>`: one per page and the largest text in the app (at most 20px) |
| Section title | `<Heading variant="small">` |
| Body | `text-sm` |
| Secondary text | `text-sm text-muted-foreground`; captions `text-xs` |

- Larger headings are allowed only on the home / landing and pricing pages.
- Numbers that line up in tables or totals use `tabular-nums`. Money, quantities and dates go through `resources/js/lib/format.ts` (`formatMoney`, `formatQuantity`, `formatDate`), never formatted by hand.
- Sentence case for titles, labels and buttons. No all-caps and no letter-spacing.

## English and Chinese

Every screen holds English and Chinese text. The default language stays English; the language switch is a separate request.

- Give text room: no width, column or button size fitted to one English label. Text wraps or truncates (`min-w-0`, `truncate`) and never overflows or overlaps. A Chinese label is shorter than an English one and has no spaces, so a layout looks right with either.
- Names, titles and messages from the user's data arrive in either language: show them as they come.

## Space and density

- Roomy. Spacing comes from the scale (`gap-1`, `gap-2`, `gap-4`, `gap-6`, `gap-8`). Page padding and section gaps are set in the layouts and the sample page of each pattern, and the height and padding of a control are set in `components/ui`: copy the classes of the sample, and change a control's size only through its `size` variant, never with `h-*` or `p-*`.
- Sections of a page are separated by space, not by cards, borders or rules.
- Desktop and phone are equal. The phone layout applies below the `md` breakpoint, where the sidebar collapses and slides out from a button. Every screen works from a 390px phone up and never scrolls sideways as a page. Each pattern says what changes on a phone: a table becomes cards, a form field's label moves above its input, a dialog slides up as a full-width sheet.

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
- Explaining text appears only for an unusual state or a step the user must take.

## Messages

| What happened | How it shows |
| --- | --- |
| An action finished (saved, deleted, sent) | a toast: `Inertia::flash('toast', ...)` in the controller, shown by the layout |
| A network error | a toast, shown by the layout |
| A message that used to be an `Alert` on the page | a toast |
| A field is wrong | `InputError` next to the field |
| An action cannot be undone | a confirmation dialog first, with the action named in full ("Delete task") |
| A region could not load | `ErrorState` (patterns/states.md) |

No `Alert` on a page.

## Accessibility

Keyboard focus stays visible. Actions are buttons and navigation is links; nothing clickable is a `div`. Text is readable against its background in both themes. Every input has a label, hidden with `sr-only` only when the layout already says what the field is.
