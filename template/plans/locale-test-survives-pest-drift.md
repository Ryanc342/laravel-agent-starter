# Locale test survives Pest drift

## Purpose
The 1.2.0 release CI failed: a project made with the Laravel Installer in an ordinary terminal fails `composer check`. Make the locale controller test survive the installer's Pest conversion so CI passes and 1.2.0 can be released.

## Agreed decisions
1. Fix the failing test so CI passes again (user: "好，修吧", 2026-11-15). Only the test setup changes; no assertion changes.

## Milestones
- [x] M1 tests: replace the named `DenyingLocalePolicy` class declared in `tests/Feature/Locale/LocaleControllerTest.php` with an anonymous class inside the test + rerun the test after `pest --drift`

## Validation
- Per milestone: `php artisan test --compact --filter=LocaleControllerTest` passes in this repository
- Drift check: in a scratch clone, `pest --drift` (what Laravel Installer 5.32.0 runs when it installs Pest), then the same test passes
- Final implementation: full `composer check` passes, repeated after any later code change
- CI `check` workflow (including `scripts/check-installer`, human mode) passes on main after the push

## Progress
- 2026-11-15 12:00 CI run 43673786207 for 27c15d6 failed: human-mode install, `LocaleControllerTest` "a policy that denies the switch fails the request": `Target class [DenyingLocalePolicy] does not exist`
- 2026-11-15 12:10 M1 done: anonymous policy class; `--filter=LocaleControllerTest` 9 passed locally; in a scratch clone after `pest --drift`, 9 passed (45 assertions)
- 2026-11-15 12:16 full `composer check` passed on current code (exit 0; Pest 425 passed, 1781 assertions; Vitest 346 passed)
- 2026-11-15 12:17 review round 1 requested from the `reviewer` subagent
- 2026-11-15 12:20 review round 1: no P0/P1 findings; docs/QUALITY.md Starter installation row updated

## Surprises & Discoveries
- Laravel Installer runs `pest --drift` (`installPest()` in laravel/installer `src/NewCommand.php`). Drift turns a named class declared in a Pest test file into a plain function, so the class disappears. It also removes `public` from methods of an anonymous class; that is harmless, since methods without a modifier are public.
- Drift also rewrites `$this->assertTrue/assertSame/assertNull` into `expect()` in EmailVerificationTest, ProfileUpdateTest and SecurityTest; those still pass, so they are left alone (not in scope).

## Decision Log
- 2026-11-15 Use an anonymous class rather than moving the policy into tests/Support because tests/Support needs the user's approval and the anonymous class keeps the test self-contained.

## Outcomes & Retrospective
Delivered: `LocaleControllerTest` declares its denying policy as an anonymous class, so the test passes after the Laravel Installer's `pest --drift`. Full `composer check` passed; review round 1 found no P0/P1. Left: push to main, confirm the CI `check` workflow (both Installer paths) passes, then tag and publish 1.2.0. Possible addition (for the user to decide): a structure test that rejects a named class declared in a Pest test file, so drift cannot break another test the same way.
