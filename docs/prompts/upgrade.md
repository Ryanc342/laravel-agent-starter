# Upgrade prompt

Use when the user asks to update dependencies, to take changes from the Laravel starter kit, or to move to a new major version of Laravel. Work in a new branch or worktree (`scripts/worktree-up upgrade`).

---

Update this project. Do not add new packages.

First decide which kind of update this is and follow only that part. If it is unclear, ask: questions come only before the plan (docs/workflow.md, step 1). Every part writes a plan (docs/PLANS.md) whose agreed decisions name the dependencies it upgrades (composer.json and package.json, with their lock files), every check it changes and every existing test assertion it changes, and waits for the user's "开始". Every part ends the same way: full `composer check`, `composer check:reference`, the final review of docs/workflow.md step 4 for every lane the update touches, screens included, and the report (step 5).

## A. Minor and patch updates (same major version)

1. Write a short plan docs/exec-plans/active/update-dependencies-<date>.md.
2. Run `composer outdated --direct` and `npm outdated`, then `composer update` and `npm update`.
3. Run `composer check`. If something fails, read the package's changelog or upgrade notes (Boost `search-docs`) and fix the code, never the check.
4. If the new Wayfinder version generates Inertia page prop types, put that in the report: the hand-written TypeScript types of the Resources can then be generated (.claude/rules/frontend.md).
5. Commit once ("Update dependencies"), then the end every part shares. The report names which packages changed version and every fix you made.

## B. Changes from the Laravel starter kit

The starter kit code was copied into this project and rewritten to the v2 rules, so its changes never arrive by themselves and are never copied over as they are.

1. Find the base commit in README.md ("Based on").
2. Clone https://github.com/laravel/react-starter-kit into a temporary folder and run `git log --oneline <base>..main` and `git diff --stat <base>..main`.
3. List the changes in the user's language, grouped (security fix, new feature, UI, tooling), one line each, with a recommendation: take or skip. Security fixes are recommended. The user decides; write the picks into a plan, docs/exec-plans/active/starter-kit-<date>.md.
4. For each change the user takes: rewrite it to the v2 rules before adding it (writes in an Action, permission in a Policy, data through a Resource, every condition in its one owner), with tests. Never add Laravel Chisel markers back. One commit per change.
5. Update the base commit in README.md (in the template, also add an entry to template/CHANGELOG.md), then the end every part shares.

## C. A new major version (for example Laravel 13 to 14)

1. Read the official upgrade guide (Boost `search-docs`, or https://laravel.com/docs/upgrade) and the upgrade notes of Inertia, Fortify, Pest, Larastan and Vite+ when their major version changes too.
2. Ask the user about every change in behaviour, then write the plan docs/exec-plans/active/upgrade-<version>.md. Milestones: the dependencies, then each breaking change from the guide, then part B for the new starter kit, then the checks.
3. Build one milestone at a time, as in docs/workflow.md, step 3.
4. Update Boost's skills (`php artisan boost:update`, or `php artisan boost:install` again if that command no longer exists). AGENTS.md and CLAUDE.md must not change.
5. Then the end every part shares.

## Projects made from this template

A project takes template releases with Copier (`copier.yml` has no questions, so every file comes as it is), one whole release at a time, on a sync branch with nothing uncommitted.

1. The project's first sync adds `.copier-answers.yml` with `_src_path: https://github.com/ryanc342/laravel-agent-starter.git` and `_commit: <the template release the project is on>`, and commits it.
2. At plan time, run `copier update --defaults --vcs-ref=<tag>`. It merges the template's changes between the two releases into the project and keeps the project's own changes. Then set `_commit` in `.copier-answers.yml` to `<tag>` (Copier does not change it, because the template has no answers file of its own).
3. Write the plan. Its agreed decisions start with `Template: <commit>` (`git rev-parse <tag>^{commit}` in the template, or `git ls-remote` on `_src_path`) and name every check and test assertion the release changes. Each conflict Copier left (a file with `<<<<<<< before updating`) is a plan question: keep the project's lines, take the template's, or combine them.
4. After the user's "开始", resolve the conflicts as answered, then run `composer check` and the final review as for any plan: a file that is as it is in the template's commit is not reviewed (docs/prompts/review.md, step 3).
