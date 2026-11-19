# Components

Read when choosing a component or a variant. It says which one to use for what; how a component is built is in its own file. Use what `resources/js/components/ui` (shadcn) and `resources/js/components/shared` already have before writing markup, and restyle through variants, not through `className` overrides of colour. Do not install another UI library. A shadcn component that is not installed yet is added with the shadcn CLI; a new package it brings is a dependency, added only when an agreed decision of the plan names it.

## Actions

| Need | Use |
| --- | --- |
| The main action of a screen, area or dialog | `Button` `default` (one per area) |
| Any other action | `Button` `outline` |
| A quiet action in a toolbar or a row | `Button` `ghost` |
| A filled neutral action next to the main one | `Button` `secondary` |
| The confirm button of something that cannot be undone | `Button` `destructive` |
| An action that looks like text inside a sentence | `Button` `link` with `size="inline"` (no height or padding of its own) |
| An icon-only action | `Button` `icon` with a `Tooltip` and an `aria-label` |
| A button that is sending | the same `Button`, `disabled`, with a `Spinner` |
| Previous and next in a list, and how many rows a page shows (10, 20, 50 or 100) | `Pagination` (`components/shared`) |
| A row of buttons | `Actions` (`components/shared`), below |

### Actions: every row of action buttons

Every row of action buttons is the shared block `Actions` (`components/shared`). It sits at the right end of its row with the main button last (Cancel, Back and the other actions first), and when its container is narrow it is full width and stacked with the main button on top. `ResponsiveDialogFooter`, `WizardLayout`'s footer, record headers (title wraps, actions stay on the right), toolbars above content (Print / Download PDF, Import / Export), settings forms and the single section buttons (Security, Delete account) all use it. A component that is not `Actions` places its own buttons: the sign-in pages' one full-width button, centred states, the landing hero, pagination, menus, tabs, the public bar, kanban columns, the month calendar's navigation and the floating bulk action bar.

Every enabled clickable element (button, link, tab, menu item, checkbox, clickable row) shows the pointing-hand cursor; a disabled one keeps the normal arrow. The cursor comes from `resources/css/app.css`, never from a `className`.

Every enabled clickable element also has a hover state (grey, foundations.md) and a press state one step darker (`active:` with a `-pressed` token), in every variant of `Button` and in the shared blocks that are clickable (menu and select items, toggles, column headers, notification rows, quick links, sidebar items). A new clickable component gets both.

## Navigation

| Need | Use |
| --- | --- |
| A link to another page, also inside text | `TextLink` |
| Where the user is, and the way back | `Breadcrumbs`, set with `Page.layout` |
| The main pages of the app | the sidebar (`mainNavItems` in `app-sidebar.tsx`) |
| The sections of settings | the settings layout's nav |
| Views of one page | `UrlTabs` (`components/shared`, shadcn `Tabs` in the pill style: a grey strip, the selected tab a white block; the selected tab is in the URL; on a phone the strip scrolls sideways; patterns/detail.md) |
| Search in the top bar and its results | `SearchBox` and `SearchResults` (`components/shared`; patterns/search-results.md) |
| The steps of a long task | `WizardLayout` (`components/shared`; patterns/wizard.md): a step the user must take has an `instruction` |
| Who did what and when | `Timeline` (`components/shared`; patterns/approval.md) |
| A list with the selected record's detail beside it | `ListDetailLayout` (`components/shared`; patterns/list-detail.md) |
| The links and buttons at the top of a public page | `PublicHeader` (`components/shared`; the public layout): logo, page links, "Log in" and "Get started" (or "Dashboard" when signed in); on a phone the links and buttons are in a `Sheet` opened by a menu button |
| The pages of a customer's account | `AccountNav` (`components/shared`; patterns/customer-area.md): one row of links, the current page in the selected colour, scrolling sideways inside its strip on a phone |

## Showing data

| Need | Use |
| --- | --- |
| A list of records | `ResponsiveTable` (`components/shared`): a `Table` on desktop, one card per row on a phone. With no records, or when it failed to load, it still shows its header row (on a phone its box) with the centred state inside |
| A stream of messages | `FeedList` and `FeedRow` (`components/shared`), as `NotificationRow` uses them (patterns/feed.md): one bordered box, rows separated by lines |
| Sort, filter and search of a column | `ColumnMenu` (`components/shared`): the header button and the `Popover` it opens, one column of equal rows with thin lines between the groups (sort, column search, tick list with Select all and Unselect all, from-to range, Clear), with its indicators: a faint grey up-down arrow at rest on every header that can be clicked, the blue up or down arrow when sorted and the blue funnel when filtered. On a phone `SortFilterSheet` (`components/shared`) holds the same choices in a `Sheet` |
| The facts of one record | a `dl`: `dt` in `text-muted-foreground`, `dd` the value |
| A status | `Badge`: `secondary` a neutral state (open, draft), `success` done or paid, `warning` needs attention, `destructive` failed, overdue or an error. The text is the label the backend sends, and it starts with a capital letter ("Open", "Done") |
| Many records ticked in a list | `BulkActionBar` and `useRowSelection` (`components/shared`): a bar floating at the bottom of the page (patterns/bulk-actions.md) |
| The range of a report | `ReportRange` (`components/shared`): presets 7 days, 30 days, 90 days and Custom (patterns/report.md) |
| Numbers over time or across categories | the chart block (`components/ui/chart.tsx`, recharts): `chart-1` for one series, `chart-1` to `chart-5` in order with a legend for several (patterns/report.md) |
| Records on their dates, by month | `MonthCalendar` (`components/shared`; patterns/calendar.md) |
| Records in status columns that cards move between | `KanbanBoard` (`components/shared`; patterns/kanban.md) |
| A user | `Avatar` |
| A row of number tiles | `StatTiles` (`components/shared`; patterns/report.md): a label and one figure per tile, backend numbers only, no placeholders; a report page has it, a dashboard has it when its plan asks for it |
| The opening of a landing page | `Hero` (`components/shared`; patterns/landing.md): the heading in the display size, one sentence, two buttons. It owns the display classes |
| What a product offers | `FeatureGrid` (`components/shared`; patterns/landing.md): icon, title and one line per feature, no card around a feature |
| Plans side by side | `PricingPlans` (`components/shared`; patterns/pricing.md): the billing `ToggleGroup` (in the URL) and one card per plan, the recommended one with a primary border and a "Recommended" `Badge`. It owns the display classes for its title and prices |
| Questions and answers | `FaqList` (`components/shared`; patterns/help.md): a search box, topics, and one bordered box of `Collapsible` rows per topic |
| The items of a cart | `LineItems` (`components/shared`; patterns/checkout.md): name, unit price, quantity `Input` (named `items[0][quantity]`) and line total |
| The totals of an order | `OrderSummary` (`components/shared`; patterns/checkout.md): subtotal, tax and total as a `dl` |
| Repeated items, a dialog, a framed tool | `Card` (never a card inside a card). Its title has no description under it |

## Inputs

| Need | Use |
| --- | --- |
| A contact form | `ContactForm` (`components/shared`; patterns/contact.md): name, email, subject and message as `FormField` rows and the send button, inside the page's own form (Inertia's `<Form>`) |
| One field in a form | `FormField` (`components/shared`): it decides from the width of its form (a container query): the label sits to the left of the input when the form is wide, above it when the form is narrow (a phone, the sign-in column, a narrow dialog). An error gives the input a red border and shows no text under it; the message is a toast, and screen readers still get it |
| How a field looks when focused or wrong | `Input`, the `Select` trigger, `Checkbox` and the `InputOTP` slot have no glow and no ring. Clicked into, the border turns the dark grey `input-focus`; with an error the border is `destructive`, and it stays red, still with no glow, when clicked into. Buttons, toggles and badges keep the blue keyboard ring, which shows only with the Tab key |
| Text | `Input`; `size="sm"` for a box inside a menu row |
| A password | `PasswordInput` |
| A one-time code | `InputOTP` |
| A list of options | `Select` |
| Yes or no | `Checkbox` |
| Several lines of text | `Textarea` (`components/ui`): the look of `Input`, three lines high, taller as the user writes |
| A date | `DatePicker` (`components/shared`): a button that opens a `Popover` with a month `Calendar`; reads and writes `YYYY-MM-DD`; never `type="date"` |
| Two to seven modes | `ToggleGroup` |
| A search box above a list | `Input` with an `sr-only` label; the list searches after a short pause in typing, or at Enter |

## Layers

| Need | Use |
| --- | --- |
| Create or edit a record that the user opened from a list | `ResponsiveDialog` (`components/shared`): a centred dialog on desktop, a full-width sheet from the bottom on a phone. It has a title and no description; its buttons are `Actions` |
| Confirm something that cannot be undone | `ConfirmDialog` (`components/shared`): the action named in full, a `consequence` line that says what will happen, and `Actions` |
| A side panel | `Sheet` |
| A small card that opens from a button (the notification bell) | `Popover` |
| A menu of actions | `DropdownMenu` |
| A row's own actions, in a list whose records have no detail page | `RowActions` (`components/shared`, through `ResponsiveTable`'s `rowActions`): one three-dot icon button that opens a menu, the destructive action last and set apart (patterns/list.md) |
| Choosing a file in a form (an import) | `FilePicker` (`components/shared`): a "Choose file" button and the chosen file's name, in the app's language; the browser's own file input stays hidden, so its English never shows |
| A hint for a control | `Tooltip` |
| Details the user opens on demand | `Collapsible` |

## Feedback

| Need | Use |
| --- | --- |
| The result of an action, a network error, a field error | a toast at the top centre, one per message (foundations.md, Messages) |
| A list or region with nothing in it | `EmptyState` (`components/shared`): centred icon and line |
| A region that could not load | `ErrorState` (`components/shared`): centred, red icon and line |
| A region that is loading | `LoadingState` (`components/shared`), made of `Skeleton` |

## Layout

| Need | Use |
| --- | --- |
| The page title, or a section title | `Heading` (`variant="small"` for a section). No description under it |
| A signed-in page | `AppLayout` |
| A sign-in page | `AuthLayout` |
| A public page (home, pricing, product, help, contact) and a customer's account pages | `PublicLayout` (`layouts/public-layout.tsx`): the top bar (`PublicHeader`), the page at a readable width, and a footer with one row of links and the copyright line. No shadow on the bar; no sidebar |
| A settings page | `SettingsLayout` |

## Not used

`Alert` (messages are toasts) and `PlaceholderPattern` (no placeholder in a real page).
