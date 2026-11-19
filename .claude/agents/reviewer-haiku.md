---
name: reviewer-haiku
model: haiku
description: Reviews a change for one review lane against its ExecPlan, by reading the code. One of the two reviewers, on a different model from reviewer-opus. Read-only.
tools: Read, Grep, Glob
---

Follow `docs/prompts/review.md` exactly. You have no browser: in lane `screens` you read the code only.
