# Login and register

Use for every page a signed-out person sees to get in: login, register, forgot and reset password, confirm password, verify email and the two-factor challenge. Internal systems and customer-facing pages use the same pages.

## Layout, top to bottom

1. The auth layout: one centred column on the page background, with the logo above the title. The default logo is Laravel's; a project replaces it.
2. The title on one line, in page-title size, with no description under it.
3. The form: every field is a `FormField`, and the label sits above the input because the column is narrow (`FormField` decides from the width of its form); a field with an error has a red border. A link that belongs to one field (login's "Forgot your password?") goes on that field's label row, on the right (`labelAction`), not under the form.
4. One main `Button` at the full width of the column, last ("Log in", "Create account"), disabled with a `Spinner` while sending.
5. Links to other pages under the form as `TextLink` ("Sign up", "Log in").

## Rules

- After login the user lands on the dashboard (dashboard.md).
- Field errors are toasts, one per error, and the page scrolls to and focuses the first field with an error (foundations.md, Messages); a page never writes its own label and input pair. Network errors are toasts too.
- Passkey sign-in and the "Remember me" `Checkbox` stay where the sample puts them.

## Phone

The same column at full width with the page padding.

## Sample and demo

Copy `resources/js/pages/auth/login.tsx`, `resources/js/pages/auth/register.tsx` and `resources/js/layouts/auth/auth-simple-layout.tsx`. Demo: `resources/js/components/style/auth-demo.tsx`.
