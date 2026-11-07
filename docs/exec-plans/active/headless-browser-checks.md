# Headless browser checks and cleanup

## Purpose
Run the template's Playwright MCP checks without opening a visible window or disturbing the user's work, and remove temporary browser-check artifacts when they are no longer needed.

## Agreed decisions
1. Run Playwright MCP headlessly for both Codex and Claude Code, using its own isolated browser session rather than the user's browser.
2. Inspect screenshots during review and delete screenshots generated for the check afterward. Report the results in text; retain requested screenshots only when the user explicitly asks to keep them.
3. On normal completion the agent calls `browser_close`, which cleans up that check's temporary screenshots. Tool errors trigger automatic close and cleanup. There is no idle fallback: if the agent forgets to close the browser, the session may remain until the server stops. If the process is forcibly terminated before cleanup, the next run cleans up its stale temporary files. Never remove existing user files, explicitly retained screenshots, or artifacts belonging to another active session.

## Milestones
- [ ] M1 Browser lifecycle and guidance: add the smallest dependency-free mechanism needed to enforce the agreed headless configuration, session-scoped cleanup and safe recovery; update the agent and review guidance and relevant public documentation; add meaningful lifecycle coverage and verify the complete checks. Commit the milestone and record the result.
- [ ] M2 Review and report: request the prescribed independent review, resolve P0/P1 findings, report verification and archive this plan.

## Validation
- Write lifecycle tests first and confirm they fail before implementing the mechanism. Cover normal exit, error exit and recovery from an interrupted session, including preservation of user files, explicitly retained screenshots and files belonging to another active session.
- Exercise both actual MCP configurations with a headless browser and screenshots; verify no browser window is opened, screenshots remain available for inspection while needed, the browser is closed and temporary files are removed afterward. Verify stale-session recovery with an interrupted disposable session.
- Verify the desktop, phone and dark-mode review requirements remain intact and the final report no longer promises deleted screenshot files by default.
- `composer check` and `composer check:reference` pass because tooling and rules change; record their results. Do not add or upgrade dependencies.

## Progress
- 2026-11-07: Recorded user decisions and prepared this plan. Implementation is awaiting the user's start instruction and approval for protected files.
- 2026-11-07 18:58 MYT: Wrote four lifecycle tests; the initial three failed before implementation, then all four passed. Both MCP configurations launched a real headless browser, created a screenshot and removed it on close; a forcibly terminated session was cleaned on the next run. The first `composer check` ran 241 Pest tests and 6 Vitest tests successfully, but the finish check rejected its record because the launcher changed during the run; rerun required.
- 2026-11-07 19:03 MYT: Review round 1: 2 P0, 1 P1. Added automatic close after tool errors and Windows launcher support, and clarified explicitly retained screenshots. Review round 2: 1 P0, 1 P1. Added process-group termination for a failed automatic close and removed the idle timer following the user's decision. New regression tests failed before each relevant fix; the final focused run passed 8 tests / 28 assertions. Review round 3: 0 P0, 0 P1. Windows command construction was simulated; no real Windows environment was available.
- 2026-11-07 19:04 MYT: Playwright MCP itself defaults to a one-hour headless idle timeout; explicitly disabled it to honor the user's no-fallback decision. The regression test failed before this change and then passed: 8 tests / 30 assertions. The reference check on the preceding version passed 292 Pest tests / 1,099 assertions and 6 Vitest tests; repeat on the final version.

## Surprises & Discoveries
- The existing MCP configurations pass `--isolated` but not `--headless`. The installed Playwright MCP help says headed mode is the default, and isolated mode only keeps the browser profile in memory.
- Existing instructions require screenshots in reports, so the review and report guidance must agree with automatic deletion.

## Decision Log
- 2026-11-07: The user selected headless mode and approved automatic temporary-screenshot cleanup, closing the browser on completion or error, and recovering stale temporary files on the next run. Explicitly requested screenshots may be retained.
- 2026-11-07: The user said “开始” after reviewing the named files, approving changes to `AGENTS.md`, `.codex/config.toml`, `docs/workflow.md`, `docs/golden-principles.md`, `docs/rules/visual-design.md`, `docs/prompts/review.md`, the new `scripts/playwright-mcp`, and `tests/Unit/Hooks/PlaywrightMcpTest.php`. `.mcp.json`, `README.md`, and `README.zh-CN.md` may also be updated. Ask before touching any additional protected file.
- 2026-11-07: After learning that idle cleanup could interrupt an ongoing review, the user chose “不用兜底”: no idle timer. The agent closes after completion; tool errors automatically close. A forgotten close may leave the session alive until server shutdown.

## Outcomes & Retrospective
Pending implementation and review.
