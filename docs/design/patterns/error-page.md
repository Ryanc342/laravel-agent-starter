# Error pages

Use when a whole page cannot be shown: 403 no permission, 404 not found, 500 server error, 503 maintenance. One Inertia error page renders all four, so they look the same.

## Layout

One centred column on the page background with no sidebar, so it also works for a signed-out visitor and when the app itself is failing. Top to bottom: the status code in `text-muted-foreground`, a `<Heading>` saying what happened, one short line saying what to do, one `Button`.

| Status | Heading | Line | Button |
| --- | --- | --- | --- |
| 403 | No permission | You don't have access to this page. | Go to dashboard (signed in) or Log in |
| 404 | Page not found | This page doesn't exist or has moved. | Go to dashboard (signed in) or Log in |
| 500 | Something went wrong | It's on our side. Try again in a moment. | Try again |
| 503 | Back soon | We're updating the system. Try again in a few minutes. | Try again |

## Rules

- No illustration, no decoration and no technical detail (no stack trace, no exception name).
- The status and the text come from the response; React does not decide which status a page has. `bootstrap/app.php` renders the page for 403, 404, 500 and 503 only, never for a JSON request, and while `APP_DEBUG` is on a 500 keeps Laravel's own page so a developer sees the exception.
- The server sends `signedIn`. A URL that matches no route has no session, so there the button is Log in even for someone signed in; the login page then takes them on to the dashboard.

## Phone

The same column at full width with the page padding.

## Sample and demo

Copy `resources/js/pages/errors/show.tsx` and `resources/js/components/errors/error-content.tsx`; the page is rendered from `bootstrap/app.php` (`$exceptions->respond`). Demo: `resources/js/components/style/error-page-demo.tsx`, shown on the style overview page.
