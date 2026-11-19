---
paths:
  - "phpstan.neon"
  - "vite.config.ts"
  - "tsconfig.json"
  - "pint.json"
  - "phpunit.xml"
  - ".npmrc"
  - "composer.json"
  - "package.json"
  - "lint/**"
  - "tests/Structure/**"
  - "template/tests/Lint/**"
---

# The checks

What `composer check` runs, and the rules that keep it whole. A check is never turned down (AGENTS.md, "Red lines"), and when a conversation changes a check file the finish check tells the user. A rule names its check in its tag (docs/golden-principles.md).

- `[Structure]` No check is silenced: no `@phpstan-ignore`, `oxlint-disable`, `eslint-disable`, `@ts-ignore` or `@ts-expect-error`, no skipped or unfinished test (`->skip()`, `->todo()`; Pest fails on them, phpunit.xml), and no `ignoreErrors`, `excludePaths` or baseline in phpstan.neon.
- `[Structure]` The checks stay as set: PHPStan's level, paths and includes and every layer rule of .claude/rules/app.md with its folders, the Ezdev oxlint rule and `import/no-cycle` on with only generated code ignored, strict TypeScript, the three Pest suites, the steps of `composer check`, no other lint config file, and no `script-shell` in .npmrc. In the template only, every check is also proved on a broken fixture (template/tests/Lint/SkeletonChecksTest.php).
- `[Structure]` Code lives only where the checks read it: resources/js holds only `.ts` and `.tsx` files in its fixed folders, docs/ holds only Markdown (docs/reference excepted), PHPStan covers every autoload folder, views and public/ hold no PHP (public/index.php excepted), nothing is required from a non-PHP file, and there are no symlinks.
