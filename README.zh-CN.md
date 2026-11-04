# Laravel Agent Starter

[English](README.md) | [简体中文](README.zh-CN.md) | [CI](https://github.com/Ryanc342/laravel-agent-starter/actions/workflows/tests.yml)

基于 Laravel 13、Inertia 3、React 19、TypeScript 和 Tailwind 的项目模板，适合使用 Codex 或 Claude Code 开发业务应用。

它在官方 [Laravel React Starter Kit](https://github.com/laravel/react-starter-kit) 的基础上，提供明确的架构、AI 开发流程和自动检查：

- **AI 有文档可依循。** `AGENTS.md` 是入口，`docs/` 记录工作流程、执行计划、分层规则和完整参考功能。
- **业务规则只有一个归属。** 按 React、HTTP、Actions / Policies、Models、Enums 分层，上层调用下层。认证、设置和通知功能也遵守这些规则。
- **机器检查结构，审查者检查业务。** PHPStan、oxlint 和结构测试约束代码边界，`composer check` 统一执行全部检查，独立 reviewer 对照计划审查业务行为。

模板保留注册、邮箱验证、双因素认证、通行密钥、密码确认等认证功能，并内置应用内通知。内部的 Ezdev 名称用于标识模板的架构检查规则。

## 环境要求

| 工具 | 版本或要求 |
| ---- | ---------- |
| PHP | 8.4.1 或更新版本，并安装 Composer 要求的扩展，包括 PDO MySQL；这是已锁定依赖要求的最低版本 |
| Composer | 2.x |
| Laravel Installer | 5.28.1 或更新版本；已验证 5.28.1 和 5.32.0 |
| Node.js | 22.x 的 22.22.2+、24.x 的 24.15.0+，或 26+；CI 使用 Node 22 |
| MySQL | 推荐 8.4，与 CI 一致；安装、开发和检查时保持运行 |
| Git | 用于初始化项目、执行计划、提交和结束检查 |

开发环境的数据库账号需要有权限创建项目数据库和测试数据库。检查使用 `<DB_DATABASE>_test`，参考功能检查使用独立的 `<DB_DATABASE>_reference_test`。

## 创建自己的项目

启动 MySQL，然后使用官方 Laravel 命令：

```sh
laravel new my-project --using=ryanc342/laravel-agent-starter
cd my-project
composer dev
```

如果安装器询问 Pest、Boost 或前端依赖，保留默认选项即可。模板会自动准备环境配置、应用密钥、项目数据库、数据库迁移、前端资源和 Git 仓库。打开 `composer dev` 显示的地址（通常为 `http://localhost:8000`），注册账号即可使用。按 Ctrl+C 停止开发进程。

**默认使用 MySQL，不需要数据库参数。** `my-project` 会使用数据库 `my_project`；检查和参考功能检查分别使用 `my_project_test` 与 `my_project_reference_test`。默认连接为 `127.0.0.1:3306`，账号 `root`，密码为空。已经显式配置的数据库名或连接 URL 会保留。

用 Codex 或 Claude Code 打开新项目，以业务语言描述需求。首次使用 Codex 时，打开 `/hooks` 并信任 `.codex/hooks.json`；Claude Code 使用 `.claude/settings.json`。

### MySQL 账号与默认值不同时

创建项目前设置连接信息，模板会将其保存到新项目本地的 `.env`：

```sh
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_USERNAME=your-user
export DB_PASSWORD='your-password'
```

账号需要有权限创建项目对应的数据库。`.env` 只保留在本地。如果初始化提示数据库连接错误，修正生成的 `.env` 后，在新项目中运行 `composer setup` 即可重试；已有的应用密钥和数据库名会保留。

### 维护这个模板

公开 Composer 包为 [ryanc342/laravel-agent-starter](https://packagist.org/packages/ryanc342/laravel-agent-starter)，稳定 Git 标签标识发布版本。官方安装器的短命令默认使用模板主分支的最新版；模板更新不会改动已经创建的项目。需要固定稳定快照时，改用 `--using=ryanc342/laravel-agent-starter:v1.0.0` 即可。

维护模板源码时，克隆本仓库，在 MySQL 运行的情况下执行 `composer setup`。`composer check` 检查源码，`composer check:reference` 同时检查参考功能。本仓库的 CI 还会通过 `scripts/check-installer`，使用真实 Laravel 安装器创建两个临时项目，验证 MySQL 隔离、开发服务器和完整检查；派生项目仓库运行常规检查。

## AI 开发流程

1. **确认需求。** AI 先查看现有代码，再按轮次提问，并给出推荐选项。可以回复“同意”，或者按编号逐项调整。
2. **写执行计划。** AI 在 `docs/exec-plans/active/` 记录已确认的决策、里程碑和验证方法；你说“开始”后才修改代码。
3. **逐个里程碑实现。** 先写测试，再从底层到上层实现，运行 `composer check`，提交并更新进度。
4. **独立审查。** reviewer 使用新上下文对照计划检查，只报告 P0、P1 问题，最多三轮。
5. **报告结果。** 说明决策由哪些文件实现、检查结果，以及界面改动的截图；GitHub CI 再执行完整检查。

结束检查 `scripts/hooks/finish-check.php` 会提示遗漏的计划、审批和验证步骤。每周清理可使用 `docs/prompts/cleanup.md`，升级依赖或同步 Laravel 上游可使用 `docs/prompts/upgrade.md`。

## 常用命令

| 命令 | 用途 |
| ---- | ---- |
| `composer setup` | 安装依赖、保留或生成密钥、迁移数据库并构建前端 |
| `composer dev` | 一起启动开发所需进程 |
| `composer check` | PHP 格式、PHPStan、前端格式和 lint、TypeScript、Vitest、Pest 全部检查 |
| `composer check:reference` | 在临时副本中加入参考功能，再运行完整检查 |
| `composer lint` | 格式化 PHP |
| `npm run check:fix` | 格式化并修复前端 lint 问题 |
| `scripts/worktree-up <name>` | 创建带独立配置、数据库和端口的 Git worktree |

## 文档入口

| 路径 | 内容 |
| ---- | ---- |
| [AGENTS.md](AGENTS.md) | AI 每次开始工作的入口；`CLAUDE.md` 指向同一文件 |
| [ARCHITECTURE.md](ARCHITECTURE.md) | 分层、业务规则归属和对应检查 |
| [DESIGN.md](DESIGN.md) | 界面设计规则和主题值 |
| [docs/workflow.md](docs/workflow.md) | 提问、计划、实现、验证和审查流程 |
| [docs/PLANS.md](docs/PLANS.md) | 执行计划格式 |
| [docs/golden-principles.md](docs/golden-principles.md) | 十项原则及其检查方式 |
| [docs/QUALITY.md](docs/QUALITY.md) | 各领域的质量记录 |
| [docs/reference/tasks/](docs/reference/tasks/) | 可参考的完整功能结构 |
| [CHANGELOG.md](CHANGELOG.md) | 版本记录 |

修改规则或工具需要用户批准，之后还需运行 `composer check:reference`。贡献方式见 [CONTRIBUTING.md](CONTRIBUTING.md)，安全问题请通过 [SECURITY.md](SECURITY.md) 中的私密渠道反馈。

## 来源与许可

基于 `laravel/react-starter-kit` 的 `98d9b82` 提交，认证和设置代码已按本模板的架构重写。项目新增内容采用 [MIT 许可证](LICENSE)。Laravel、随模板提供的 Laravel 技能和 shadcn/ui 组件保留上游版权声明；安装的第三方依赖分别遵循各自许可证。
