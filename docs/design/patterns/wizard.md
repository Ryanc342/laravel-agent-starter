# Multi-step wizard

Use for a long task that has natural steps (setting up a team, onboarding, a quote with several parts). It is the one exception to "a record opens in a dialog" (form.md): a wizard is its own page.

## Layout, top to bottom

1. Header row: `<Heading>` with the task's name ("Set up team").
2. Below it, `WizardLayout` (`components/shared`): the step list on the left and the current step's fields on the right. Each step in the list is its number and name; the current one uses the selected colour (`bg-accent-pressed`), a finished one shows a check. Steps are not links: the user moves with the buttons.
3. The fields of the current step: one column of `FormField`s, as in form.md.
4. The buttons last, at the right end of the row: "Back" (`outline`, hidden on step 1), then "Next" (`default`) as the rightmost. The last step's main button names the result ("Create team"), disabled with a `Spinner` while sending.

## Rules

- "Next" checks the fields of the current step on the server; the next step shows only when they pass. Errors are toasts and red borders, as everywhere (form.md).
- The steps and their validation rules are defined by the backend (a FormRequest per step, or one with the step's rules); React holds only which step is showing and what was typed so far.
- Nothing is saved until the last step.

## Phone

The step list becomes one line at the top ("Step 2 of 3 · Team"); the fields and buttons stack below it, the buttons at full width and stacked, the main one on top.

## Demo

`resources/js/components/style/wizard-demo.tsx`, shown on the style overview page.
