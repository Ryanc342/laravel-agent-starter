# Contributing

Thank you for helping improve Laravel Agent Starter. English and Chinese issues and pull requests are welcome.

## Before making a change

For a bug, open an [issue](https://github.com/Ryanc342/laravel-agent-starter/issues) with reproduction steps, expected and actual behavior, and relevant PHP, Node.js and MySQL versions. Remove credentials and private data from logs. Report vulnerabilities through [SECURITY.md](SECURITY.md).

For a feature or change to the template's rules, discuss the scope in an issue before implementing it. Read [AGENTS.md](AGENTS.md), the skeleton (`.claude/rules/skeleton.md`) and the cards of the blocks you change (`.claude/rules/skeleton/`). The repository's [workflow](docs/workflow.md) and its workflow tool record decisions and approval in a plan; AI contributors must follow it.

## Local development

1. Fork the repository, clone your fork, and create a branch for the change.
2. Follow the requirements and installation steps in [README.md](README.md), using your own database name.
3. Keep changes within the agreed scope. Add meaningful behavior tests for application changes, and keep existing assertions intact unless the requirement has changed.
4. Run `composer check` with MySQL running.
5. Open a pull request describing the problem, resulting behavior, agreed decisions, and validation results. Include screenshots for UI changes and link the issue and the plan when applicable.

The architecture and checks apply equally to the template's built-in features and new features. Do not skip tests or silence checks, and add a dependency, change a check or change an existing test assertion only as a decision of the plan (`docs/PLANS.md`).

## Licensing

Contributions to the project's own code are provided under the [MIT license](LICENSE). Preserve upstream copyright notices when copying or updating third-party material, and identify its source in the pull request.
