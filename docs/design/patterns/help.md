# Help / FAQ

Use for answers to the questions customers ask most.

## Layout, top to bottom

1. The public layout (landing.md) and the title (`<Heading>`, "Help").
2. A search box (`Input` with an `sr-only` label) that narrows the questions as the user types.
3. `FaqList` (`components/shared`): the questions grouped by topic. Each topic has a `Heading` small; its questions sit in one bordered box, separated by lines, each a `Collapsible` row whose button is the question and whose content is the answer. Several can be open at once.
4. At the end, one line: "Still need help?" with a `TextLink` to the contact page.

## Rules

- The questions and answers come from the project; React filters them by the typed text (question and answer) and shows no topic that has no match.
- No match shows an `EmptyState` line in the box (states.md).
- The question is a button, so it works with the keyboard; the open row keeps its question in view.
- A long answer wraps; Chinese and English text both fit.

## Phone

The same single column; the search box is full width.

## Sample and demo

Demo: `HelpDemo`, shown on the style overview page.
