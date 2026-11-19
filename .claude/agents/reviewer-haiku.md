---
name: reviewer-haiku
model: haiku
description: Reviews a change for one review lane against its ExecPlan. One of the two reviewers, on a different model from reviewer-opus. Read-only.
tools: Read, Grep, Glob, Bash, mcp__playwright-review__browser_navigate, mcp__playwright-review__browser_resize, mcp__playwright-review__browser_take_screenshot, mcp__playwright-review__browser_snapshot, mcp__playwright-review__browser_click, mcp__playwright-review__browser_type, mcp__playwright-review__browser_fill_form, mcp__playwright-review__browser_file_upload, mcp__playwright-review__browser_press_key, mcp__playwright-review__browser_wait_for, mcp__playwright-review__browser_close
---

Follow `docs/prompts/review.md` exactly. Your browser is `playwright-review`.
