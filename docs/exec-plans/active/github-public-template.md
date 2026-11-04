# Publish Laravel Agent Starter

## Purpose

Publish this project as the public GitHub template `Ryanc342/laravel-agent-starter`, with enough documentation for other developers to install and use it. Rename the local project directory to `/Users/ryan/Projects/laravel-agent-starter` while retaining its Git history and local configuration.

## Agreed decisions

1. Use `laravel-agent-starter` as the repository name, under the authenticated GitHub account `Ryanc342`; make it public and enable GitHub's template repository setting.
2. Use MIT for the project's own additions, credit `Ryanc342`, and retain applicable upstream copyright and license notices.
3. Preserve the existing 69 commits. Inspect the tracked files and complete reachable Git history for credentials and private information before publishing; never include secret values in reports.
4. Keep an English README and add a Chinese README, with accurate requirements, installation, development startup, checks, and template usage instructions.
5. Add a LICENSE, contribution instructions, and security reporting instructions. Validate installation and all existing checks, publish the repository, and verify GitHub CI succeeds.
6. Rename the local project folder from `Ezdev-Template` to `laravel-agent-starter`; check references to the old absolute path and verify commands still work from the new location.
7. Follow the existing plan, review, and verification workflow. The user explicitly instructed implementation to start on 2026-11-04.

## Milestones

- [x] M1 Prepare the public template: audit current files and Git history, check upstream and bundled-material notices, update `README.md`, add `README.zh-CN.md`, `LICENSE`, `CONTRIBUTING.md`, and `SECURITY.md`, and record publication validation in `docs/QUALITY.md`. Run `composer check`, request the reviewer subagent's review against this plan, resolve P0/P1 findings, and commit.
- [x] M2 Prove installation and rename locally: install from a clean temporary checkout using a separate smoke-test database, run `composer setup`, `composer check`, and `composer check:reference`, then rename the local directory and verify Git, dependencies, configuration, and `composer check` from its new path. Record results and commit the plan updates.
- [ ] M3 Publish and verify: create `Ryanc342/laravel-agent-starter` as a public template repository, configure a private security reporting channel, connect `origin`, push `main` with the retained history, and wait for GitHub CI. Complete the reviewer round for any subsequent changes, fill in the outcome, move this plan to `completed/`, and push the final records; confirm CI passes for the final pushed commit.

## Validation

- A full reachable-history secret scan and a tracked-file audit finish before the first push. Local `.env`, authentication material, generated dependencies, logs, and machine-specific files are excluded; investigate any findings without exposing secret values.
- License notices are checked against the upstream starter kit and bundled third-party material. The new LICENSE describes the project's own contributions without replacing third-party notices.
- Both READMEs agree on requirements and commands, link to each other, and point to the final repository. All local documentation links resolve.
- `composer setup` succeeds in a clean checkout with its own database; the installation procedure includes `composer dev` and explains required MySQL access.
- `composer check` and `composer check:reference` pass; no existing test assertions or checks are weakened. Documentation and directory naming do not require new application tests.
- A reviewer subagent follows `docs/prompts/review.md`, reviews the full change from the original commit `fd85e7fcca6924f70e63dd2a33a5b231ab4e8331`, and reports no unresolved P0/P1 findings, with at most three rounds.
- `/Users/ryan/Projects/laravel-agent-starter` exists, the previous project path is no longer the project directory, all original commits remain reachable, and `composer check` passes from the new path.
- GitHub reports `visibility=public`, `is_template=true`, default branch `main`, the expected `origin`, a working private vulnerability reporting channel, and a successful check workflow on the final pushed commit.

## Progress

- 2026-11-04 20:12 MYT Inspected the repository, existing CI, documentation, GitHub authentication, and local directory references. The working tree was clean at commit `fd85e7f`; the active plan directory was empty. Recorded the user's publication decisions and additional request to rename the local directory. Implementation has not started.
- 2026-11-04 20:14 MYT The user said to start. Gitleaks v8.30.1 (download checksum verified) scanned all reachable Git history with redacted reporting: 69 commits, zero findings. The tracked-file/history path audit found no actual `.env`, credential files, or database dumps. Baseline `composer check` passed: Pint, PHPStan, frontend lint, TypeScript, 6 Vitest tests, and 224 Pest tests (868 assertions). No application test is needed for documentation and a directory rename.
- 2026-11-04 20:17 MYT Prepared the five public documents, verified local Markdown links, and rescanned all 405 publishable files: zero secret findings. `composer check` passed again with 224 Pest tests (868 assertions) and 6 Vitest tests. Review round 1: 0 P0, 1 P1; the locked Symfony dependencies require PHP >=8.4.1 while the original CI and initial documentation specified 8.3. Corrected both READMEs; a one-line change to `.github/workflows/check.yml` requires user approval before editing.
- 2026-11-04 20:21 MYT Review round 2: 0 P0, 0 P1; the PHP documentation finding is resolved. M1 documentation is ready. A clean checkout with no installed dependencies completed `composer setup`, `composer check` (224 Pest tests, 868 assertions; 6 Vitest tests), and `composer check:reference` (272 Pest tests, 1,025 assertions; 6 Vitest tests), using its own temporary database. `composer dev` served `/`, `/login`, and `/register` with HTTP 200. Publication remains gated on approval to correct the original CI PHP version.
- 2026-11-04 20:21 MYT Committed M1 documentation and its validation records as `4e37f77` (`Prepare Laravel Agent Starter for public template use`).
- 2026-11-04 20:22 MYT Renamed the project to `/Users/ryan/Projects/laravel-agent-starter`; the previous directory no longer exists. Git identifies the new worktree path, and the original history remains reachable. `composer check` passed from the new path: 224 Pest tests (868 assertions), 6 Vitest tests, and all static checks. No active configuration refers to the old absolute path. Stopped the smoke development processes and removed their temporary checkout and three isolated databases. M2 is complete; GitHub creation and push remain pending the one-line protected CI correction approval.

## Surprises & Discoveries

- The repository has 69 commits and no remote. GitHub CLI is authenticated as `Ryanc342`; no accessible repository exists at the selected target name.
- `composer.json` declares MIT, but the repository has no root license file. The upstream Laravel React starter kit declares MIT in its README rather than providing a root LICENSE file; bundled material still needs its own provenance check.
- CI already runs `composer setup`, `composer check`, and `composer check:reference` with PHP 8.3, Node 22, and MySQL 8.4. No CI file change is currently planned.
- The actual `.env` is not tracked. The inspected MCP and hook configurations use relative paths or dynamically resolved project roots. Only one Git worktree is registered, and the requested destination directory does not currently exist.
- The READMEs and public repository name can change without renaming internal Ezdev rule identifiers or modifying the application's behavior.
- The committed Symfony dependencies require PHP >=8.4.1, despite the project's broad PHP constraint and existing CI's PHP 8.3 selection. The verified local runtime is PHP 8.4.20. CI must select PHP 8.4 before publication can be verified; no dependency update is needed.
- A clean `npm install` changes only the root display name in its copy of `package-lock.json` to match the checkout directory; package versions and dependency requirements remain unchanged. The original lock file is unchanged.

## Decision Log

- 2026-11-04 The user accepted `laravel-agent-starter` after rejecting `ezdev-template`, then explicitly accepted all remaining publication recommendations: MIT with attribution, retained and inspected history, English and Chinese documentation, and complete preparation and publication as a public GitHub template.
- 2026-11-04 The user additionally requested renaming the local project folder; use the accepted repository name for the folder and perform the move after installation validation, with subsequent commands using the new absolute path.
- 2026-11-04 The authorized changes currently cover publication documentation, the execution plan, `docs/QUALITY.md`, repository settings, the Git remote, and the local directory name. No protected rule, tool, CI, manifest, dependency, or existing test assertion change is planned. Any newly necessary protected-file change requires the user's approval recorded here with its exact file name before editing.
- 2026-11-04 The user explicitly said "开始", authorizing execution of the recorded plan, including public publication and the local folder rename.
- 2026-11-04 Pending user approval: change only `php-version: '8.3'` to `php-version: '8.4'` in `.github/workflows/check.yml`, because existing locked dependencies require >=8.4.1. Do not edit the protected workflow until approved; then record that approval here and verify both check commands.

## Outcomes & Retrospective

Public documentation is prepared and reviewed, fresh installation and both check commands pass, and the local project is now `/Users/ryan/Projects/laravel-agent-starter`. Publication is awaiting explicit approval for the one-line PHP version correction in `.github/workflows/check.yml`. No public repository has been created and no code has been pushed. Record the final repository URL and successful GitHub CI here after publication.
