# Laravel Agent Starter

[English](README.md) | [简体中文](README.zh-CN.md) | [CI](https://github.com/Ryanc342/laravel-agent-starter/actions/workflows/check.yml)

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
| Node.js | 22.x 的 22.22.2+、24.x 的 24.15.0+，或 26+；CI 使用 Node 22 |
| MySQL | 推荐 8.4，与 CI 一致；安装、开发和检查时保持运行 |
| Git | 用于克隆、执行计划、提交和结束检查 |

开发环境的数据库账号需要有权限创建项目数据库和测试数据库。检查使用 `<DB_DATABASE>_test`，参考功能检查使用独立的 `<DB_DATABASE>_reference_test`。

## 创建自己的项目

1. 点击 [**Use this template**](https://github.com/new?template_name=laravel-agent-starter&template_owner=Ryanc342)，创建自己的仓库，然后克隆：

   ```sh
   git clone https://github.com/YOUR-ACCOUNT/YOUR-PROJECT.git
   cd YOUR-PROJECT
   cp .env.example .env
   ```

2. 启动 MySQL，编辑 `.env`：将 `DB_DATABASE` 设为独立的项目数据库名，例如 `my_project`，并配置 `DB_HOST`、`DB_PORT`、`DB_USERNAME` 和 `DB_PASSWORD`。`.env` 只保留在本地，Git 中只提交 `.env.example`。
3. 安装并检查：

   ```sh
   composer setup
   composer check
   ```

   `composer setup` 根据已提交的锁文件安装 PHP 和 Node 依赖、生成应用密钥、按需创建数据库、运行迁移并构建前端。这个命令用于首次安装，每次执行都会重新生成应用密钥。

4. 启动开发服务器：

   ```sh
   composer dev
   ```

   打开终端显示的地址，通常为 `http://localhost:8000`，注册账号即可使用。命令会一起启动开发所需进程，按 Ctrl+C 停止。
5. 用 Codex 或 Claude Code 打开项目，以业务语言描述需求。首次使用 Codex 时，打开 `/hooks` 并信任 `.codex/hooks.json` 中的结束检查；Claude Code 通过 `.claude/settings.json` 运行该检查。

如果想先试用模板，也可以直接克隆本仓库：

```sh
git clone https://github.com/Ryanc342/laravel-agent-starter.git
cd laravel-agent-starter
cp .env.example .env
```

然后从上面的第 2 步继续。这是源代码模板，使用时不需要发布或安装 npm 包。

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
| `composer setup` | 首次安装依赖、生成密钥、迁移数据库并构建前端 |
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
