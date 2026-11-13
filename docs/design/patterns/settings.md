# Settings

Use for the user's account settings and, in a project, for app settings.

## Layout

- `resources/js/layouts/settings/layout.tsx` wraps every settings page: the "Settings" heading, a vertical nav of the sections on the left (Profile, Security, Appearance), the section's content on the right.
- A section is a `<Heading variant="small">` with no description under it, and its form, on the page itself. Settings are not records, so no dialog (form.md). The content keeps a readable width.
- Fields use `FormField`: label on the left of the input on desktop, above it on a phone.
- Each form has its own save button ("Save changes"), disabled while sending. A toast says that it saved (foundations.md, Messages).
- An action that cannot be undone (delete account) sits in its own section at the bottom, a heading and the button, with no warning box above it. The confirmation dialog first says in one sentence what will happen.

## Phone

The section nav moves above the content, with a `Separator` between them. The forms fill the width.

## States

A section that could not load uses `ErrorState`; one with nothing to show uses `EmptyState` (states.md).

## Sample and demo

`resources/js/layouts/settings/layout.tsx` and the pages in `resources/js/pages/settings/`. Demo: `resources/js/components/style/settings-demo.tsx`. The language switch is not part of this pattern yet.
