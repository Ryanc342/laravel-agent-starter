# Settings

Use for the user's account settings and, in a project, for app settings.

## Layout

- `resources/js/layouts/settings/layout.tsx` wraps every settings page: the "Settings" heading, a vertical nav of the sections on the left (Profile, Security, Appearance), the section's content on the right.
- A section is a `<Heading variant="small">` with no description under it, and its form, on the page itself. Settings are not records, so no dialog (form.md). The content keeps a readable width.
- Fields use `FormField`, which puts the label beside or above the input by the width of the form. Every form sets `noValidate` (form.md).
- Each form has its own save button ("Save changes") in the form's `Actions`, disabled while sending. A toast says that it saved (foundations.md, Messages).
- An action that cannot be undone (delete account) sits in its own section at the bottom, a heading and the button (in `Actions` too), with no warning box above it. The `ConfirmDialog` first says in its `consequence` what will happen.

## Phone

The section nav moves above the content, with a `Separator` between them. The forms fill the width.

## States

A section that could not load uses `ErrorState`; one with nothing to show uses `EmptyState` (states.md).

## Sample and demo

`resources/js/layouts/settings/layout.tsx` and the pages in `resources/js/pages/settings/`. Demo: `resources/js/components/style/settings-demo.tsx`. The Appearance section holds the theme and, next to it, the "Language" choice: a `ToggleGroup` with "English" and "中文", each named in its own language (.claude/rules/translations.md).
