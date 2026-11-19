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

`DESIGN.md` in the project root is the style of every screen and an index of the design spec. The details are in `docs/design/`: `foundations.md` (colour, type, language, space, shape, motion, copy, messages), `components.md` (which component and variant for what) and one file per page pattern in `docs/design/patterns/`. The spec controls appearance only; the architecture, business rules, folders and the data contract win over it. A project with its own brand changes the primary colour and the logo and nothing else.

These are the Do's and Don'ts of every screen. A rule names its check in its tag, with its lane and level, and says why in one sentence (docs/golden-principles.md): both reviewers check `[review:screens]` in the changed code, and `reviewer-opus` also on screenshots of the changed pages and against the style overview page (`/style`, development only), in the final review's screen round (docs/workflow.md, step 4).

## Every page

Read DESIGN.md first. Its index names the files for your task: foundations.md for colour, type, space and messages, components.md for which component to use, and the pattern file of the page you build. Keep light and dark mode working, and keep every screen working with English and Chinese text. Compare the page with `/style`: its token, component and pattern demos show how each should look. Make no browser pass of your own before the final review's screen rounds; fix what they find.

## Look

- `[review:screens:2]` Colors, fonts, corners and spacing come from the theme tokens in resources/css/app.css that docs/design/foundations.md names, each defined once there (light values in `:root`, dark values in `.dark`, mapped to Tailwind classes in `@theme`); fonts load through the `fonts` list in vite.config.ts. Why: a value written outside app.css is missed when the theme changes.
- `[Structure]` The spec names tokens and component sizes, never values: no pixel value or Tailwind size class in DESIGN.md or docs/design/ (tests/Structure/DesignDocsTest.php).
- `[Structure]` Every pattern file in docs/design/patterns/ is in the DESIGN.md index and has its Sample or Demo section, and every file a pattern file names exists (tests/Structure/DesignDocsTest.php).
- `[review:screens:2]` Outside components/ui, no Tailwind palette colour (`bg-blue-500`), hex or `rgb()`, `hsl()`, `oklch()` or `oklab()` colour in a class (`className`, a `*ClassName` prop, `cn`, `clsx`, `cva`, `twMerge` or `classNames`) or in `style`, no arbitrary value in a class, and no `dark:` colour override: use a theme token (`bg-primary`, `text-muted-foreground`, `var(--chart-1)`). Why: a colour outside the tokens breaks dark mode and the brand colour. Not a break: an arbitrary variant such as `data-[state=open]:`.
- `[review:screens:3]` A `components/ui` component (`Button`, `Input`, `Textarea`, `SelectTrigger`, `Toggle`, `ToggleGroupItem`, `Badge`, `Checkbox`, `InputOTPSlot`), `PasswordInput` and `DatePicker` are resized only through their `size` variant (`sm`, `lg`, `icon`), never with an `h-*`, `size-*` or `p*-*` class. Why: a hand-sized control stands out from every other one. Not a break: a `[…]:` or `*:` child variant.
- `[review:screens:3]` One primary colour, blue, marks the main action of a screen; `destructive`, `success` and `warning` only mean a status, never decoration. Why: a colour used for decoration stops meaning anything.
- `[review:screens:3]` Cards hold only repeated items, dialogs and framed tools: no bordered box directly inside another (a hand-drawn `rounded-lg border` counts like `Card`), no page section styled as a floating card, and corners from the radius scale. Why: boxes inside boxes make every screen look different. Not a break: the repeated items of a list or board, such as a kanban card in its column or a phone card in a list, and the demo frame on `/style`.
- `[review:screens:3]` The page title is `<Heading>`, the largest text on the page; sections, panels, rows and tables use `Heading`'s smaller variants, one font family, and only `Hero` (its heading) and `PricingPlans` (the pricing page's title and prices) draw larger text, so the display classes (`text-display`, `text-display-lg`) appear only in components/shared/hero.tsx and pricing-plans.tsx. Why: a page that picks its own sizes looks different from every other.
- `[review:screens:3]` No gradients, glows, blurred orbs or decorative backgrounds, no row of identical stat cards with big numbers as the page opener (number tiles are `StatTiles`, under Behaviour), no all-caps or letter-spaced labels, and no second accent colour. Why: decoration competes with the work on the screen.
- `[review:screens:3]` Flat: only what floats over the page (dropdown, popover, dialog, sheet, toast) has a shadow; borders separate everything else. Why: a shadow then always means "this floats".
- `[review:screens:3]` Hover is the neutral `accent` grey, never blue, and every enabled clickable element has a press state one step darker than its hover (`active:` with a `-pressed` token), without moving or resizing. Why: a click must feel the same everywhere.
- `[review:screens:3]` Motion is a quick transition on hover, open, close or expand, with nothing moving when the user asks for reduced motion, and no looping or decorative animation. Why: motion that does not show a change distracts, and some users get sick from it.
- `[review:screens:2]` A page that matches a pattern in docs/design/patterns uses that pattern and copies the structure of the sample or demo it names. Why: a pattern built twice in two ways drifts apart. Not a break: a page with no pattern, built from foundations.md and components.md, which the report names.
- `[review:screens:3]` A list of records is a `ResponsiveTable`, with the record's name, status and amount as its first columns. Why: every list then searches, sorts, filters and becomes phone cards the same way. Not a break: a notification or activity list (next rule).
- `[review:screens:3]` A notification or activity list is a feed: `FeedRow`s in one bordered box with line-separated rows, no card per row, no grey row background and no button on a row. Why: a feed reads as one stream, not as a list of records.
- `[review:screens:3]` The sort, filters and search of a list live in the URL only. Why: a list state outside the URL is lost on reload and cannot be shared.
- `[review:screens:3]` A page about one record has a breadcrumb that ends at its list, the record's name appears once, as the page heading, and there is no back button. Why: two ways back, or the name twice, is noise.
- `[review:screens:3]` A form field is a `FormField`. Why: it places the label and shows an error the same way in every form.
- `[review:screens:3]` A status is a `Badge` with a solid fill (`secondary`, `success`, `warning` or `destructive`) and the label the backend sends. Why: a status then looks the same on every page.
- `[review:screens:1]` At desktop and phone width, in English and in Chinese, text stays inside its box and nothing overlaps: long text wraps or truncates, and no page scrolls sideways. Why: overflowing text hides content and breaks the page on a phone.
- `[review:screens:2]` An existing component (components/ui, components/shared) is used instead of hand-styled markup, and restyled through its variants, never by overriding its colours with `className`. Why: hand-styled copies of a component drift from it.
- `[review:screens:3]` Every row of action buttons is the shared `Actions` block, in reading order with the main button last. Why: buttons then sit and stack the same way in every dialog, header and form.
- `[review:screens:3]` A list whose records have no detail page puts each row's actions in one three-dot menu (`RowActions`, through `ResponsiveTable`'s `rowActions`), a single action too, with the destructive action last; a list with a detail page has no action column (patterns/list.md). Why: buttons in rows crowd the list and differ from list to list.

## Behaviour

- `[review:screens:3]` The first screen after login is the dashboard, a working screen. Why: the user starts where the work is.
- `[review:screens:1]` A dashboard shows only data that arrives from the backend: no placeholders and no made-up numbers, and a row of number tiles is `StatTiles` with backend numbers only (on a report page, and on a dashboard when its plan asks for it). Why: a made-up number is wrong data.
- `[review:screens:3]` Explaining text appears only for an unusual state (an error, an empty list, a locked record) or a step the user must take: no description line under a title (page, section, card or dialog), no sentence that explains what a feature does, no warning box above a button that already asks for confirmation, no slogans, no text about how the screen was built, and nothing shown twice; a form field has a line under it only for a step the user must take (the password requirements), nowhere else. Why: text nobody needs pushes the work down the screen. Not a break: the `consequence` of a `ConfirmDialog` and the `instruction` of a wizard or two-factor step.

Other details sit behind a Tooltip, a collapsible section or a detail page.

- `[review:screens:3]` Creating or editing a record that the user opened from a list is a `ResponsiveDialog` (`wide` for a form with a table of lines); a page whose purpose is the form (sign-in, settings, contact, checkout, a wizard) is that page. Why: every record is then created and edited the same way.
- `[review:screens:3]` Every `<Form>` and `<form>` sets `noValidate` (not `noValidate={false}`) and its inputs keep `required`. Why: the browser's own message comes in the browser's language, while the server's comes in the app's; `required` still tells screen readers.
- `[review:screens:3]` Messages are toasts at the top centre (results of actions, network errors, field errors), and no page shows an `Alert`. Why: messages then appear in one place.

The shared hooks cut the field-error toasts to five plus "And N more errors" and scroll to the first field with an error (no animation under reduced motion).

- `[review:screens:3]` Familiar controls: a Select for a list of options, a Checkbox for yes or no, a ToggleGroup for two to seven modes, tabs for views. Why: an unusual control makes the user stop and think.
- `[review:screens:3]` An icon-only `Button` (`size="icon…"`), the three-dot button of `RowActions` too, has a `Tooltip` and `aria-label={t('…')}` or `aria-labelledby`. Why: an icon alone does not say what it does, to anyone or to a screen reader.
- `[review:screens:3]` An empty list shows `EmptyState`, a region that failed to load `ErrorState`, and data that arrives later `LoadingState`, inside the table or box that frames the list. Why: these states then look the same everywhere.
- `[review:screens:1]` An action that cannot be undone, a delete or a bulk delete too, asks in a `ConfirmDialog` first. Why: one wrong click otherwise loses data.
- `[review:screens:1]` Every input has a label, visible unless the layout already says what the field is (a search box next to a Search button), and then hidden with `sr-only` or `aria-label`, never left out. Why: an input without a label cannot be used with a screen reader.
- `[review:screens:1]` The submit button is disabled while the form is sending. Why: a second click sends the form twice.
- `[review:screens:1]` Keyboard focus stays visible, actions are buttons and navigation is links (no clickable `div`), and every enabled clickable element shows the pointing-hand cursor (a disabled one the arrow). Why: otherwise a keyboard or mouse user cannot tell what to press.
- `[review:screens:1]` Text is readable against its background, in light mode and in dark mode. Why: unreadable text is a broken page.
- `[review:screens:3]` A button label says what happens ("Save changes", "Delete task", not "Submit" or "OK"), and an error message says how to fix the problem. Why: the user should not have to guess.
- `[review:screens:2]` A change to a screen touches only what the plan names: the rest of the page stays as it was. Why: a change no decision names is reviewed against nothing and surprises the user.

Inside what the plan changes, removing or simplifying is preferred over adding.

- `[review:screens:3]` A date field is the shared `DatePicker` (components/shared/date-picker.tsx), never `type="date"` on `input` or `Input`. Why: the browser's own date field looks and behaves differently in every browser.
- `[review:screens:3]` A chart is the chart block with `chart-1` for one series and `chart-1` to `chart-5` in order, with a legend, for several, never a hex or status colour. Why: charts then read the same in light and dark mode.
- `[review:screens:3]` The range of a report and its selected tab live in the URL. Why: a report state outside the URL is lost on reload and cannot be shared.
- `[review:screens:1]` Ticking rows shows the floating `BulkActionBar`, and "Select all N results" covers exactly the list's own search and filters (patterns/bulk-actions.md). Why: a bulk action on rows the user did not mean changes or deletes the wrong records.
- `[review:screens:1]` An import checks every row first, imports nothing when a row is wrong, and returns every row error (patterns/import-export.md). Why: a half-imported file leaves data the user cannot find.
- `[review:screens:1]` An export is the list as the user sees it: its search, filters and sort. Why: an export that differs from the screen gives the user wrong data.
- `[review:screens:1]` A public page (the landing page and the customer pages) ships no invented reviews, customer logos, user numbers or stock photos: it shows only what the project supplies, and a picture block stays a neutral placeholder. Why: invented proof is false information about the business.

## Text and translations

How a sentence is written and translated: .claude/rules/translations.md.

- `[review:screens:3]` Text a user reads is never written straight into JSX: text children (template literals too) and the `title`, `placeholder`, `aria-label`, `ariaLabel`, `label`, `alt`, `consequence`, `instruction`, `submitLabel`, `footer`, `footerLink`, `emptyMessage`, `searchLabel`, `backLabel`, `more` and `message` props go through `t()` or `tChoice()` from `@/lib/i18n`, in every file outside components/ui and the code Wayfinder generates. Why: a sentence outside `t()` is never translated.
- `[review:screens:3]` Every sentence the change adds to `t()`, `tChoice()`, `msg()`, `__()`, `trans()`, `trans_choice()`, `Lang::get()`, `@lang` or `sentence()` has a non-empty Chinese with the same placeholders in `lang/zh_CN.json` (a group key such as `auth.failed` in `lang/zh_CN/`), and every sentence the change removes has its translation removed. Why: a missing translation shows English to a Chinese user, and a dead one is never cleaned up. Not a break: the sentences Laravel and Fortify send, which count as used.

The zh_CN validation, auth, passwords and pagination groups hold every key of lang/en (`custom.*` excepted).

- `[review:screens:3]` Every sentence a user reads is translated: dates and numbers go through the current language, and the only English left on a screen in Chinese is user content. Why: a mixed-language screen looks broken. Not a break: what users typed, and the example data a demo receives.
- `[oxlint]` `t()` and `tChoice()` from `@/lib/i18n` never run at module level: outside every function, also inside a module-level array or object and in a class field, they run before the language is set and keep the English sentence. Mark the sentence with `msg()` and call `t()` where it is shown (`ezdev/no-module-level-t`).
