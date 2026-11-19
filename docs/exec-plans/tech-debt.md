# Tech debt

Known debt, oldest first. Add a line when you defer something. Fixing a line is a request like any other (docs/workflow.md): the questions, the plan, the user's "开始". When it is fixed, remove the line.

| Added | Where | What | Why deferred |
| ----- | ----- | ---- | ------------ |
| 2026-11-20 | resources/js/pages/auth/register.tsx, reset-password.tsx, settings/security.tsx | Show the password requirements under the password fields (register, reset password, security). The explaining-text rule allows that one line. | Template 1.6.0 changes no page beyond the sidebar (decision 38). |
