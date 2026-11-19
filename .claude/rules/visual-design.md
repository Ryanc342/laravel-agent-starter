---
paths:
  - "resources/js/**"
  - "resources/css/**"
  - "resources/views/**"
  - "lang/**"
  - "DESIGN.md"
  - "docs/design/**"
---

# Visual design

`DESIGN.md` in the project root is the style of every screen and an index of the design spec. The details are in `docs/design/`: `foundations.md` (colour, type, language, space, shape, motion, copy, messages), `components.md` (which component and variant for what) and one file per page pattern in `docs/design/patterns/`. The spec controls appearance only; the architecture, business rules, folders and the data contract win over it. A project with its own brand changes the primary colour and the logo and nothing else. These files are protected (AGENTS.md, "Red lines").

These are the Do's and Don'ts of every screen. A rule names its check in its tag (docs/golden-principles.md): both reviewers check `[review:screens]` in the changed code, and `reviewer-opus` also on screenshots of the changed pages and against the style overview page (`/style`, development only), in the final review's screen round (docs/workflow.md, step 4).

## Every page

Read DESIGN.md first. Its index names the files for your task: foundations.md for colour, type, space and messages, components.md for which component to use, and the pattern file of the page you build. Keep light and dark mode working, and keep every screen working with English and Chinese text. Compare the page with `/style`: its token, component and pattern demos show how each should look. Make no browser pass of your own before the final review's screen rounds; fix what they find.

## Look

- `[review:screens]` Colors, fonts, corners and spacing come from the theme tokens in resources/css/app.css that docs/design/foundations.md names. Every value is defined once there (light values in `:root`, dark values in `.dark`, mapped to Tailwind classes in `@theme`); to change a value, change it in app.css only. Fonts load through the `fonts` list in vite.config.ts.
- `[Structure]` The spec names tokens and component sizes, never values: no pixel value or Tailwind size class in DESIGN.md or docs/design/ (tests/Structure/DesignDocsTest.php).
- `[Structure]` Every pattern file in docs/design/patterns/ is in the DESIGN.md index and has its Sample or Demo section, and every file a pattern file names exists (tests/Structure/DesignDocsTest.php).
- `[review:screens]` Outside components/ui, no Tailwind palette colour (`bg-blue-500`), hex or `rgb()`, `hsl()`, `oklch()` or `oklab()` colour in a class (`className`, a `*ClassName` prop, `cn`, `clsx`, `cva`, `twMerge` or `classNames`) or in `style`, no arbitrary value in a class (an arbitrary variant such as `data-[state=open]:` is fine), and no `dark:` colour override: use a theme token (`bg-primary`, `text-muted-foreground`, `var(--chart-1)`).
- `[review:screens]` A `components/ui` component (`Button`, `Input`, `Textarea`, `SelectTrigger`, `Toggle`, `ToggleGroupItem`, `Badge`, `Checkbox`, `InputOTPSlot`), `PasswordInput` and `DatePicker` are never resized with an `h-*`, `size-*` or `p*-*` class (a `[…]:` or `*:` child variant is fine): their `size` variant does it (`sm`, `lg`, `icon`).
- `[review:screens]` One primary color, blue, for the main action of a screen; `destructive`, `success` and `warning` only mean a status, never decoration.
- `[review:screens]` Cards hold only repeated items, dialogs and framed tools: no bordered box directly inside another bordered box (a hand-drawn `rounded-lg border` counts like `Card`; the repeated items of a list or board, such as a kanban card in its column or a phone card in a list, are the repeated items), no page section styled as a floating card, and corners from the radius scale. The demo frame on `/style` is a light tinted surface with no border.
- `[review:screens]` The page title is `<Heading>`, the largest text on the page, and sections, panels, rows and tables use `Heading`'s smaller variants; one font family. Only the two blocks of the next rule draw larger text: `Hero` its heading, `PricingPlans` the pricing page's title and its prices.
- `[review:screens]` The display sizes belong to the two blocks that use them, `Hero` and `PricingPlans`: a page cannot pick a heading size, and the display classes (`text-display`, `text-display-lg`) appear only in components/shared/hero.tsx and pricing-plans.tsx.
- `[review:screens]` No gradients, glows, blurred orbs or decorative backgrounds, no row of identical stat cards with big numbers as the page opener (number tiles are `StatTiles`, under Behaviour), no all-caps or letter-spaced labels, and no second accent colour.
- `[review:screens]` A feed row has no card and no grey background.
- `[review:screens]` Flat: only what floats over the page (dropdown, popover, dialog, sheet, toast) has a shadow; borders separate everything else.
- `[review:screens]` Hover is a neutral grey (the `accent` token), never blue, and every enabled clickable element has a press state one step darker than its hover colour (`active:` with a `-pressed` token) without moving or resizing; a selected toggle or current sidebar page uses the press colour; a column header that can be clicked always shows the faint up-down arrow.
- `[review:screens]` Motion is a quick transition on hover, open, close or expand, and nothing moves when the user asks for reduced motion; no looping or decorative animation.
- `[review:screens]` A page that matches a pattern in docs/design/patterns uses that pattern and copies the structure of the sample or demo it names; a page with no pattern is built from foundations.md and components.md, and the report says so.
- `[review:screens]` A list of records is a `ResponsiveTable`: its name, status and amount first, horizontal row lines only, one search box above it, sort and filters in the column headers (a column menu; on a phone a "Sort & filter" sheet), one card per row on a phone, and with no records it still shows its header row (or box) with the centred empty state inside. Sort, filters and search live in the URL only. A notification or activity list is one bordered box with line-separated rows: no card per row, no grey row background and no button on a row; clicking an unread row marks it as read.
- `[review:screens]` A page about one record has a breadcrumb that ends at its list, and the record's name appears once, as the page heading; there is no separate back button, and no "Back to …" button next to a breadcrumb that already links back.
- `[review:screens]` A form field is a `FormField`, which decides from the width of its form: the label sits to the left of the input when the form is wide, above it when the form is narrow (a phone, the sign-in column, a narrow dialog); an error gives it a red border and no text under it.
- `[review:screens]` A status is a `Badge` with a solid fill (`secondary`, `success`, `warning` or `destructive`) and the label the backend sends, starting with a capital letter.
- `[review:screens]` At desktop and phone width, in English and in Chinese, text stays inside its box and nothing overlaps; long text wraps or truncates, and no page scrolls sideways.
- `[review:screens]` An existing component (components/ui, components/shared) is used instead of hand-styled markup, and restyled through its variants, not by overriding its colors with `className`.
- `[review:screens]` Every row of action buttons is the shared `Actions` block (`components/shared`): at the right end of its row with the main button last, and full width and stacked with the main button on top when its container is narrow. `ResponsiveDialogFooter`, `WizardLayout`'s footer, record headers, toolbars, settings forms and the single section buttons use it; a component that is not `Actions` places its own buttons.
- `[review:screens]` A list whose records have no detail page puts each row's actions in one three-dot menu (`RowActions` through `ResponsiveTable`'s `rowActions`), a single action too: no other button in a row, the destructive action last and set apart, at the top right of the card on a phone. A list with a detail page has no action column (patterns/list.md).

## Behaviour

- `[review:screens]` The first screen after login is the dashboard, a working screen, and the layout does the explaining: no secondary labels or descriptions, no description line under any title (page, section, card or dialog), no help text under every field, no sentence that explains what a feature does, no warning box above a button that already asks for confirmation, no slogans, no text about how the screen was built or what was asked for, and nothing shown twice.
- `[review:screens]` A dashboard shows only data that arrives from the backend: no placeholders and no made-up numbers. A row of number tiles is `StatTiles` (backend numbers only) on a report page, and on a dashboard when its plan asks for it.
- `[review:screens]` Explaining text appears only for an unusual state (an error, an empty list, a locked record) or a step the user must take; other details sit behind a Tooltip, a collapsible section or a detail page. A sentence under a title has fixed places only: the `consequence` of a `ConfirmDialog` for something that cannot be undone (what will happen), and the `instruction` of a step the user must take (a wizard step, a two-factor step). `ResponsiveDialog` has no description.
- `[review:screens]` One rule for where a form lives: creating or editing a record that the user opened from a list is a `ResponsiveDialog` (on a phone it slides up from the bottom at full width); a page whose purpose is the form (sign-in, settings, contact, checkout, a wizard) is that page.
- `[review:screens]` Every `<Form>` and `<form>` sets `noValidate` (not `noValidate={false}`), so the browser never shows its own message in the browser's language: an empty required field reaches the server, whose message comes back in the app's language. Inputs keep `required`, so that screen readers announce the field.
- `[review:screens]` Messages are toasts at the top centre: results of actions, network errors, field errors (one toast per error, at most five plus one "And N more errors", for forms and imports alike) and what an `Alert` used to show; no `Alert` on a page. After a failed submit the page or dialog scrolls to the first field with an error and focuses it (no animation under reduced motion).
- `[review:screens]` Familiar controls: a Select for a list of options, a Checkbox for yes or no, a ToggleGroup for two to seven modes, tabs for views.
- `[review:screens]` An icon-only `Button` (`size="icon…"`), the three-dot button of `RowActions` too, has a `Tooltip` and `aria-label={t('…')}` or `aria-labelledby`.
- `[review:screens]` An empty list shows `EmptyState`, a region that failed to load `ErrorState`, data that arrives later `LoadingState`, all three centred inside the table or box that frames the list.
- `[review:screens]` An action that cannot be undone, a delete or a bulk delete too, asks in a `ConfirmDialog` first.
- `[review:screens]` Every input has a label. It is visible unless the layout already says what the field is (a search box next to a Search button), and then it is hidden with `sr-only` or `aria-label`, never left out. The submit button is disabled while the form is sending.
- `[review:screens]` Keyboard focus stays visible, actions are buttons and navigation is links (no clickable `div`), every enabled clickable element shows the pointing-hand cursor and a disabled one the arrow, text is readable against its background, and light and dark mode both work.
- `[review:screens]` Button labels say what happens ("Save changes", "Delete task", not "Submit" or "OK"), and an error message says how to fix the problem.
- `[review:screens]` A change to a screen changes only what the plan names: the rest of the page stays as it was, and removing or simplifying is preferred over adding.
- `[review:screens]` A date field is the shared `DatePicker` (components/shared/date-picker.tsx), which reads and writes `YYYY-MM-DD`: no `type="date"` on `input` or `Input`.
- `[review:screens]` A chart is the chart block with `chart-1` for one series and `chart-1` to `chart-5` in order with a legend for several, never a hex or status colour; the range of a report and the selected tab live in the URL.
- `[review:screens]` Ticking rows shows the floating `BulkActionBar` with the count and the actions, "Select all N results" covers the list's own search and filters; an import returns every row error, which the validation toasts show (five plus "And N more errors"), and imports nothing when a row is wrong; an export is the list as the user sees it (patterns/bulk-actions.md, patterns/import-export.md).
- `[review:screens]` A public page (the landing page and the customer pages) ships no invented reviews, customer logos, user numbers or stock photos: it shows only what the project supplies, and a picture block stays a neutral placeholder.

## Text and translations

How a sentence is written and translated: .claude/rules/translations.md.

- `[review:screens]` Text a user reads is never written straight into JSX: text children (template literals too) and the `title`, `placeholder`, `aria-label`, `ariaLabel`, `label`, `alt`, `consequence`, `instruction`, `submitLabel`, `footer`, `footerLink`, `emptyMessage`, `searchLabel`, `backLabel`, `more` and `message` props go through `t()` or `tChoice()` from `@/lib/i18n`, in every file outside components/ui and the code Wayfinder generates.
- `[review:screens]` Every sentence passed to `t()`, `tChoice()`, `msg()`, `__()`, `trans()`, `trans_choice()`, `Lang::get()`, `@lang` or `sentence()` has a non-empty Chinese in `lang/zh_CN.json` (a group key such as `auth.failed` in `lang/zh_CN/`); no entry is unused (the sentences Laravel and Fortify send count as used); the placeholders match; and the zh_CN validation, auth, passwords and pagination groups hold every key of lang/en (`custom.*` excepted).
- `[review:screens]` Every sentence a user reads is translated: dates and numbers go through the current language, what users typed is never translated, and the only English left on a screen in Chinese is user content, which includes the example data a demo receives.
- `[oxlint]` `t()` and `tChoice()` from `@/lib/i18n` never run at module level: outside every function, also inside a module-level array or object and in a class field, they run before the language is set and keep the English sentence. Mark the sentence with `msg()` and call `t()` where it is shown (`ezdev/no-module-level-t`).
