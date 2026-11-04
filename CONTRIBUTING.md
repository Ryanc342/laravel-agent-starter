# Contributing

Thank you for helping improve Laravel Agent Starter. English and Chinese issues and pull requests are welcome.

## Before making a change

For a bug, open an [issue](https://github.com/Ryanc342/laravel-agent-starter/issues) with reproduction steps, expected and actual behavior, and relevant PHP, Node.js and MySQL versions. Remove credentials and private data from logs. Report vulnerabilities through [SECURITY.md](SECURITY.md).

For a feature or change to the template's rules, discuss the scope in an issue before implementing it. Read [AGENTS.md](AGENTS.md), [ARCHITECTURE.md](ARCHITECTURE.md), and the applicable `docs/rules/` files. The repository's [workflow](docs/workflow.md) describes how decisions and approval are recorded in an execution plan; AI contributors must follow it.

## Local development

1. Fork the repository, clone your fork, and create a branch for the change.
2. Follow the requirements and installation steps in [README.md](README.md) or [README.zh-CN.md](README.zh-CN.md), using your own database name.
3. Keep changes within the agreed scope. Add meaningful behavior tests for application changes, and keep existing assertions intact unless the requirement has changed.
4. Run `composer check` with MySQL running. For rules, tooling or reference changes, also run `composer check:reference`.
5. Open a pull request describing the problem, resulting behavior, agreed decisions, and validation results. Include screenshots for UI changes and link the issue and execution plan when applicable.

The architecture and checks apply equally to the template's built-in features and new features. Do not skip tests, silence checks, add dependencies, or alter protected files without the approval required by `AGENTS.md`.

## Licensing

Contributions to the project's own code are provided under the [MIT license](LICENSE). Preserve upstream copyright notices when copying or updating third-party material, and identify its source in the pull request.
