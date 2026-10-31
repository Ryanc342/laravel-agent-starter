# First run of the template

## Purpose
Install the dependencies, finish the Laravel Boost setup and prove that every check passes on this machine. The template was assembled in an environment that could not reach Packagist or npm, so no PHP or npm command has run on it yet.

## Agreed decisions
1. Laravel Boost provides MCP tools and skills only. AGENTS.md and CLAUDE.md stay hand-written.
2. Formatting changes made by `composer lint` and `npm run check:fix` are accepted as they are.
3. A check that fails because of the template's own configuration (tests/Arch, tests/Structure, phpstan.neon, .dependency-cruiser.cjs) is fixed in that configuration without weakening the rule. Anything that would weaken a rule is asked first.
4. The version constraints of the added dev packages (Pest, spaze/phpstan-disallowed-calls, Boost, dependency-cruiser) may be moved to the newest compatible versions.
5. The reference feature in docs/reference/tasks was rewritten for v2 and has not run yet. If it fails, fix the reference itself.

## Milestones
- [ ] M1 Install: `composer install` (writes composer.lock), `cp .env.example .env`, `php artisan key:generate`, `touch database/database.sqlite`, `php artisan migrate`, `npm install` (writes package-lock.json), `npm run build`. If `php artisan install:features` asks which auth features to keep, keep the defaults unless the user says otherwise.
- [ ] M2 Boost: `php artisan boost:install`. Choose Claude Code and Codex, enable the MCP server and skills, do not enable AI guidelines. Then `git diff -- AGENTS.md CLAUDE.md` must be empty; if Boost changed them, restore them with `git checkout -- AGENTS.md CLAUDE.md` and set `"guidelines": false` in boost.json.
- [ ] M3 Format: `composer lint`, then `npm run check:fix`. Commit.
- [ ] M4 Checks: `composer check` passes. Log every template-level fix in the Decision Log. Commit.
- [ ] M5 Reference: `composer check:reference` passes. Commit any fix to docs/reference/tasks.
- [ ] M6 Prove the checks bite, on a throwaway branch: a controller that calls `$request->user()->update([...])` must fail PHPStan with "writes belong in an Action"; a component in resources/js/components/a importing one from resources/js/components/b must fail dependency-cruiser; an Action calling `Gate::authorize()` must fail the Arch test. Delete the branch afterwards.

## Validation
- `composer check` and `composer check:reference` pass.
- composer.lock, package-lock.json, boost.json and the generated skills folders are committed.
- `git diff 301f330 -- AGENTS.md CLAUDE.md` shows only the template's own edits, nothing from Boost.
- M6 shows each of the three failures with its message.

## Progress
- 2026-10-31 Template assembled on top of laravel/react-starter-kit main (98d9b82). PHP syntax checked with `php -l`; nothing else has run.

## Surprises & Discoveries
- None yet.

## Decision Log
- None yet.

## Outcomes & Retrospective
Fill this in when done, then move this file to docs/exec-plans/completed/.
