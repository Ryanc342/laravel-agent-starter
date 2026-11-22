---
paths:
  - "resources/js/components/**"
  - "resources/js/layouts/**"
  - "resources/js/pages/**"
---

# PAGE>COMP: Pages and layouts → components

From: PAGE (Pages), LAYOUT (Layouts)  
To: COMP (Components)

This card applies to every file at either end. Every rule here applies in full; the tag says who guarantees it.

## Carries

- **PAGE>COMP-1** The options a component offers. `note`

## Before it passes

- **PAGE>COMP-2** Markup that a ui or shared component provides (table, pagination, dialog, confirmation, badge, button, link, tabs, bulk bar, boxed field, icon button, a record's facts, page header, section, form) is not hand-written in a page or feature component; when the component does not fit, the change adds an option or variant to it. `reviewer`
  - Check: Look at: markup in pages and feature components. Break: a hand-written table, pagination, dialog, confirmation, badge, button, tabs, bulk bar, boxed field (an Input with its own label), icon-only button, a dl of a record's facts, a page header row, a titled section or a form with its own spacing. Not: using the component with its options; a new option added to the component in the same change. Cite: the line and the component to use.

## Must not

- **PAGE>COMP-3** Change a component's looks: a className a page passes to a component follows the page rule and holds layout only. `reviewer`
  - Check: Look at: className a page or a feature component passes to a ui or shared component. Break: colour, size, border, radius, shadow or font classes on it. Not: layout classes (as PAGE-9 lists). Cite: the line.
