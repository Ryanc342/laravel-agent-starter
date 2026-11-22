# Existing debt

Problems in older code that a plan found and left as they were (FLOW-5), one item per line. The workflow tool adds the ones the reviewers list, counts them in every report, and at 10 brings a draft cleanup plan; `php scripts/workflow.php cleanup` drafts one any time. A fixed item leaves this list.

- resources/js/pages/auth/register.tsx, reset-password.tsx, settings/security.tsx: the password fields do not show the password requirements under them. (found while reviewing template-1.6.0)
- tests/Structure/ProjectFiles.php:63 read(): a missing or unreadable file reads as empty text; the structure checks built on it then fail rather than pass, but without naming the read. (found while reviewing template-1.6.0)
- resources/js/pages/auth/verify-email.tsx: the page does not say what to do (open the email and click its link); it shows only the title and the Resend button. (found while reviewing template-2.0.0)
- resources/js/pages/auth/reset-password.tsx:28: the email field is labelled "Email" where every other page says "Email address". (found while reviewing template-2.0.0)
