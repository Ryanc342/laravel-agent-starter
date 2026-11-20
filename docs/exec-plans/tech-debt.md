# Tech debt

Known debt, oldest first. Add a line when you defer something. Fixing a line is a request like any other (docs/workflow.md): the questions, the plan, the user's "开始". When it is fixed, remove the line.

| Added | Where | What | Why deferred |
| ----- | ----- | ---- | ------------ |
| 2026-11-20 | resources/js/pages/auth/register.tsx, reset-password.tsx, settings/security.tsx | Show the password requirements under the password fields (register, reset password, security). The explaining-text rule allows that one line. | Template 1.6.0 changes no page beyond the sidebar (decision 38). |
| 2026-11-20 | scripts/screen-round.php, tests/Structure/DesignDocChecks.php, tests/Unit/Hooks/ScreenRoundTest.php and PlaywrightMcpTest.php | Reads, writes and removals behind `@`, or with an unchecked `file_put_contents`/`copy`/`touch`, and the `shell_exec` git call of the scripts/screen-round shim, can fail without a word (fixes.md, an error is never swallowed); the port probes that use a failure as their answer are the exception. Since 1.6.0 the two tests make their folders and write their fixtures through tests/Support/Workspace.php; their reads, the shutdown kills and ScreenRoundTest's `rename` of the project folder are still left. | Found by the 1.6.0 review in code from before that plan. |
| 2026-11-20 | tests/Structure/ProjectFiles.php, `read()` | A missing or unreadable file reads as empty text; the structure checks built on it then fail rather than pass, but without naming the read. | Found by the 1.6.0 review in code from before that plan. |
