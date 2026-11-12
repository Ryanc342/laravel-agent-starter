# Login and register

Use for every page a signed-out person sees to get in: login, register, forgot and reset password, confirm password, verify email and the two-factor challenge. Internal systems and customer-facing pages use the same pages.

## Layout, top to bottom

1. The auth layout: one centred column on the page background, with the logo above the title. The default logo is Laravel's; a project replaces it.
2. The title on one line, in page-title size. A description only when the title alone is unclear or the user must take a step.
3. The form: fields stacked with the label above each input, because the column is narrow (the one place a label does not sit on the left on desktop). `InputError` under each field.
4. One main `Button` at the full width of the column, last ("Log in", "Create account"), disabled with a `Spinner` while sending.
5. Links under the form as `TextLink` ("Forgot your password?", "Sign up").

## Rules

- After login the user lands on the dashboard (dashboard.md).
- Field errors show next to the field (`InputError`). Network errors are toasts.
- Passkey sign-in and the "Remember me" `Checkbox` stay where the sample puts them.

## Phone

The same column at full width with the page padding.

## Sample and demo

Copy `resources/js/pages/auth/login.tsx`, `resources/js/pages/auth/register.tsx` and `resources/js/layouts/auth/auth-simple-layout.tsx`. Demo: `resources/js/components/style/auth-demo.tsx`.
