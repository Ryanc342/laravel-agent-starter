# Upgrade prompt

Use when the user asks to update dependencies, to take changes from the Laravel starter kit, or to move to a new major version of Laravel. Work in a new branch or worktree (`scripts/worktree-up upgrade`).

---

Update this project. The user asked for it, which is the approval AGENTS.md requires for upgrading dependencies. Do not add new packages.

First decide which kind of update this is and follow only that part. If it is unclear, ask.

## A. Minor and patch updates (same major version)

1. Run `composer outdated --direct` and `npm outdated`, then `composer update` and `npm update`.
2. Run `composer check`. If something fails, read the package's changelog or upgrade notes (Boost `search-docs`) and fix the code, never the check.
3. Run `composer check:reference`.
4. Commit once ("Update dependencies"). Report which packages changed version and every fix you made.

## B. Changes from the Laravel starter kit

The starter kit code was copied into this project and rewritten to the v2 rules, so its changes never arrive by themselves and are never copied over as they are.

1. Find the base commit in README.md ("Based on").
2. Clone https://github.com/laravel/react-starter-kit into a temporary folder and run `git log --oneline <base>..main` and `git diff --stat <base>..main`.
3. List the changes in the user's language, grouped (security fix, new feature, UI, tooling), one line each, with a recommendation: take or skip. Security fixes are recommended. The user decides.
4. For each change the user takes: rewrite it to the v2 rules before adding it (writes in an Action, permission in a Policy, data through a Resource, every condition in its one owner), with tests. Never add Laravel Chisel markers back.
5. Run `composer check` and `composer check:reference`. One commit per change.
6. Update the base commit in README.md and add a CHANGELOG.md entry.

## C. A new major version (for example Laravel 13 to 14)

1. Read the official upgrade guide (Boost `search-docs`, or https://laravel.com/docs/upgrade) and the upgrade notes of Inertia, Fortify, Pest, Larastan and Vite+ when their major version changes too.
2. Grill the user on every change in behaviour, then write the ExecPlan docs/exec-plans/active/upgrade-<version>.md (docs/PLANS.md). Milestones: the dependencies, then each breaking change from the guide, then part B for the new starter kit, then the checks.
3. Build one milestone at a time, with `composer check` after each, as in docs/workflow.md.
4. Finish with `composer check:reference` and Playwright screenshots of the login, settings and notifications pages.
5. Update Boost's skills (`php artisan boost:update`, or `php artisan boost:install` again if that command no longer exists). AGENTS.md and CLAUDE.md must not change.

## Projects made from this template

A project made from the template is not linked to it. To bring template changes (rules, checks, docs) into a project, read the template's CHANGELOG.md, list the changes for the user, copy what the user picks, and run the project's `composer check`.
