# Components

Read when choosing a component or a variant. It says which one to use for what; how a component is built is in its own file. Use what `resources/js/components/ui` (shadcn) and `resources/js/components/shared` already have before writing markup, and restyle through variants, not through `className` overrides of colour. Do not install another UI library. A shadcn component that is not installed yet is added with the shadcn CLI; ask the user first if it brings a new package.

## Actions

| Need | Use |
| --- | --- |
| The main action of a screen, area or dialog | `Button` `default` (one per area) |
| Any other action | `Button` `outline` |
| A quiet action in a toolbar or a row | `Button` `ghost` |
| A filled neutral action next to the main one | `Button` `secondary` |
| The confirm button of something that cannot be undone | `Button` `destructive` |
| An action that looks like text inside a sentence | `Button` `link` |
| An icon-only action | `Button` `icon` with a `Tooltip` and an `aria-label` |
| A button that is sending | the same `Button`, `disabled`, with a `Spinner` |
| Previous and next in a list | `Pagination` (`components/shared`) |

## Navigation

| Need | Use |
| --- | --- |
| A link to another page, also inside text | `TextLink` |
| Where the user is, and the way back | `Breadcrumbs`, set with `Page.layout` |
| The main pages of the app | the sidebar (`mainNavItems` in `app-sidebar.tsx`) |
| The sections of settings | the settings layout's nav |
| Views of one page | `Tabs` (not installed yet; add it when the first page needs it) |

## Showing data

| Need | Use |
| --- | --- |
| A list of records | `ResponsiveTable` (`components/shared`): a `Table` on desktop, one card per row on a phone |
| A stream of messages | `FeedRow` (`components/shared`), as `NotificationRow` uses it (patterns/feed.md) |
| The facts of one record | a `dl`: `dt` in `text-muted-foreground`, `dd` the value |
| A status | `Badge`: `secondary` a neutral state (open, draft), `success` done or paid, `warning` needs attention, `destructive` failed, overdue or an error. The text is the label the backend sends |
| A user | `Avatar` |
| Repeated items, a dialog, a framed tool | `Card` (never a card inside a card) |

## Inputs

| Need | Use |
| --- | --- |
| One field in a form | `FormField` (`components/shared`): label on the left of the input on desktop, above it on a phone, `InputError` under the input |
| Text | `Input` |
| A password | `PasswordInput` |
| A one-time code | `InputOTP` |
| A list of options | `Select` |
| Yes or no | `Checkbox` |
| Two to seven modes | `ToggleGroup` |
| A search box beside its button | `Input` with an `sr-only` label |

## Layers

| Need | Use |
| --- | --- |
| Create or edit a record, or confirm something that cannot be undone | `ResponsiveDialog` (`components/shared`): a centred dialog on desktop, a full-width sheet from the bottom on a phone |
| A side panel | `Sheet` |
| A small card that opens from a button (the notification bell) | `Popover` |
| A menu of actions | `DropdownMenu` |
| A hint for a control | `Tooltip` |
| Details the user opens on demand | `Collapsible` |

## Feedback

| Need | Use |
| --- | --- |
| The result of an action, a network error | a toast (foundations.md, Messages) |
| A list or region with nothing in it | `EmptyState` (`components/shared`) |
| A region that could not load | `ErrorState` (`components/shared`) |
| A region that is loading | `LoadingState` (`components/shared`), made of `Skeleton` |

## Layout

| Need | Use |
| --- | --- |
| The page title, or a section title | `Heading` (`variant="small"` for a section) |
| A signed-in page | `AppLayout` |
| A sign-in page | `AuthLayout` |
| A settings page | `SettingsLayout` |

## Not used

`Alert` (messages are toasts) and `PlaceholderPattern` (no placeholder in a real page).
