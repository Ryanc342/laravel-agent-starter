# Contact form

Use for a page where a visitor writes to the business.

## Layout, top to bottom

1. The public layout (landing.md) and the title (`<Heading>`, "Contact us").
2. `ContactForm` (`components/shared`): name, email, subject and message as `FormField` rows (`FormField` puts the label beside or above the input by the width of the form; the message is a `Textarea`). An error gives the input a red border and shows no text under it.
3. One `default` `Button` last, named for what happens ("Send message"), disabled with a `Spinner` while sending.

## Rules

- The server validates (a FormRequest). A failed send shows one toast per error and scrolls to and focuses the first field with an error (form.md); a successful send shows a success toast and clears the form.
- No captcha, phone number or office address is invented: the project adds what it has.
- The email address is the only way the business answers; the page says nothing about response times.

## Phone

The same column, the labels above the inputs, the button at full width.

## Sample and demo

Demo: `resources/js/components/style/contact-demo.tsx`, shown on the style overview page. It takes its validation answer from its example data, as the other demos do.
