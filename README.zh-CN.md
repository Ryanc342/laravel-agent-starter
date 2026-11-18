# Laravel Agent Starter

[English](README.md) | [简体中文](README.zh-CN.md) | [CI](https://github.com/Ryanc342/laravel-agent-starter/actions/workflows/tests.yml)

基于 Laravel 13、Inertia 3、React 19、TypeScript 和 Tailwind 的项目模板，适合使用 Claude Code 开发业务应用。

它在官方 [Laravel React Starter Kit](https://github.com/laravel/react-starter-kit) 的基础上，提供明确的架构、AI 开发流程和自动检查：

- **AI 有文档可依循。** `AGENTS.md` 是入口，`docs/` 记录工作流程、执行计划、分层规则和完整参考功能。
- **业务规则只有一个归属。** 按 React、HTTP、Actions / Policies、Models、Enums 分层，上层调用下层。认证、设置和通知功能也遵守这些规则。
- **机器检查骨架，两个 AI 审查者检查其余规则。** 机器只检查 18 条规则：分层（PHPStan，`phpstan.neon` 中的分层规则）、组件边界、循环导入和不在导入时调用 `t()`（oxlint，Ezdev 规则在 `lint/oxlint/`）、`app/` 的目录、页面和组件的位置、没有检查被关闭或放宽，以及设计规范的索引和取值（结构测试）。`composer check` 统一执行全部检查。其余 106 条规则分为六个审查通道，需要阅读代码，由两个使用不同模型的审查子代理 `reviewer-opus` 和 `reviewer-haiku` 审查每次改动。AI 准备结束时，结束检查会退回遗漏的流程步骤，包括审查轮次。

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

用 Claude Code 打开新项目，以业务语言描述需求。模板只支持 Claude Code：审查轮次需要它的子代理和 hooks（`.claude/settings.json`）。

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
3. **逐个里程碑实现。** 先写测试，再从底层到上层实现。运行 `composer check:milestone -- <受影响的测试路径>`：全量格式、静态分析和类型检查，加上受影响的测试，包括没有修改但受影响的调用方测试。提交并记录检查范围、命令和结果。整份计划实现完成后运行完整 `composer check`，最终审查改动代码后重新全量验证；共享测试/数据库基础设施变更或影响范围无法可靠界定时提前全量验证。
4. **独立审查。** 所有审查都在完整 `composer check` 之后的最终审查里一次完成，里程碑之间没有审查轮次。每一轮让两个审查者在同一个提交上同时运行，改动涉及的每个审查通道各运行一次（`docs/golden-principles.md` 的 Review lanes）。某个通道在最新一轮里两个审查者都没有报告 P0 和 P1、代码已提交、且该通道涉及的文件之后没有改动，这个通道才算完成；AI 修复问题后开始下一轮，轮数不设上限。界面放在最后：其他通道都完成后，`look` 和 `behaviour` 两个通道在两个席位上同时运行。`scripts/screen-round` 负责准备：`reviewer-opus` 用 `playwright` 浏览器打开项目自己的应用，`reviewer-haiku` 用 `playwright-review` 浏览器打开审查副本（`../<项目目录>-review`，独立的数据库和端口）；两者都通过表单走完计划里的流程，并在 1280px、390px 宽度和深色模式下查看每个改动的页面。参考功能（`docs/reference/tasks/`）的改动也和应用代码一样审查：`scripts/screen-round --reference` 让两个席位都使用装好参考功能的副本。审查者只能运行只读的 git 命令：其他命令都会在运行前被 hook 拒绝并记录。
5. **报告结果。** 说明决策由哪些文件实现、检查结果和界面检查结果；截图默认是临时文件，只有你明确要求时才保留。GitHub CI 再执行完整检查。

结束检查 `scripts/hooks/finish-check.php` 会提示遗漏的计划、审批、验证和审查轮次。有已完成及待完成里程碑的有效进行中计划，可以使用当前代码的里程碑检查结果；最终验收必须通过完整 `composer check`，部分检查不能记作全量通过。每次审查运行都会在审查记录（review ledger）里写一行（由 Claude Code 的子代理 hooks 写入），结束检查读取这份记录，而不是 AI 自己写的内容：计划涉及的每个通道都完成后，计划才算完成。

审查轮次会给每次改动增加时间和模型费用：每个涉及的通道至少运行两次审查，每次修复后还要再审，界面轮次还要驱动两个浏览器。模板只支持 Claude Code，因为审查轮次需要它的子代理和 hooks。每周清理可使用 `docs/prompts/cleanup.md`，升级依赖或同步 Laravel 上游可使用 `docs/prompts/upgrade.md`。

## 常用命令

| 命令 | 用途 |
| ---- | ---- |
| `composer setup` | 安装依赖、保留或生成密钥、迁移数据库并构建前端 |
| `composer dev` | 一起启动开发所需进程 |
| `composer check` | PHP 格式、PHPStan、前端格式和 lint（含 oxlint）、TypeScript、Vitest、Pest（Unit、Feature、Structure）全部检查 |
| `composer check:static` | 全量 PHP/前端格式、lint、静态分析和类型检查 |
| `composer check:milestone -- <测试路径>` | 全量静态检查及指定的 PHP/前端测试；在计划里记录影响范围 |
| `composer check:reference` | 在临时副本中加入参考功能，再运行完整检查 |
| `composer lint` | 格式化 PHP |
| `npm run check:fix` | 格式化并修复前端 lint 问题 |
| `scripts/worktree-up <name>` | 创建带独立配置、数据库和端口的 Git worktree |

## 文档入口

| 路径 | 内容 |
| ---- | ---- |
| [AGENTS.md](AGENTS.md) | AI 每次开始工作的入口；`CLAUDE.md` 指向同一文件 |
| [ARCHITECTURE.md](ARCHITECTURE.md) | 分层、业务规则归属和对应检查 |
| [DESIGN.md](DESIGN.md) | 界面风格，以及按任务查看哪份设计文件的索引 |
| [docs/design/](docs/design/) | 设计基础、组件选择和二十六种页面模式（列表、详情、表单、空 / 错误 / 加载状态、仪表盘、设置、消息流、登录注册、错误页、批量操作、导入导出、打印、搜索结果、向导、审批、列表加详情、报表、日历、看板，以及面向客户的页面：首页 / 落地页、价格、产品、客户中心、结账、帮助 / 常见问题和联系表单）；开发时打开 `/style` 查看全部演示 |
| [docs/workflow.md](docs/workflow.md) | 提问、计划、实现、验证和审查流程 |
| [docs/PLANS.md](docs/PLANS.md) | 执行计划格式 |
| [docs/golden-principles.md](docs/golden-principles.md) | 十项原则、每条规则由谁检查，以及六个审查通道 |
| [docs/prompts/review.md](docs/prompts/review.md) | 每次审查运行读什么、如何报告 |
| [docs/QUALITY.md](docs/QUALITY.md) | 各领域的质量记录 |
| [docs/reference/tasks/](docs/reference/tasks/) | 可参考的完整功能结构 |
| [CHANGELOG.md](CHANGELOG.md) | 版本记录 |

修改规则或工具需要用户批准，之后还需运行 `composer check:reference`。18 条机器检查是骨架，其余规则都是 `docs/golden-principles.md` 里的 `[review]` 规则，留给审查者；每周清理（`docs/prompts/cleanup.md`）按通道逐条检查 `[review]` 规则，列出 18 条机器检查的缺口和可以合并或删除的规则，由你决定，检查只会变得更严格。贡献方式见 [CONTRIBUTING.md](CONTRIBUTING.md)，安全问题请通过 [SECURITY.md](SECURITY.md) 中的私密渠道反馈。

## 多语言

应用支持英文和简体中文。第一次访问时按浏览器的 `Accept-Language` 选择（任何中文都用中文，其他用英文）；用户在“设置 → 外观”里切换，访客用公开页顶栏和登录页右上角的“EN / 中文”切换。选择会保存在用户（`users.locale`）和 `locale` cookie 里，由 `SetLocale` 应用到每个请求。只有一个来源，Laravel 的语言文件，不装任何包：句子以英文原文为键（`lang/zh_CN.json`，`"Save changes": "保存更改"`），Laravel 自带的分组在 `lang/zh_CN/`，React 通过 `t()` 和 `tChoice()`（`resources/js/lib/i18n.ts`）读取当前语言的句子。每个句子都用英文写在 `t()` 或 `__()` 里，并在同一次提交里补上中文；缺少或多余的翻译、直接写在 JSX 里的文字由审查者报告（`behaviour` 通道）；在导入时就运行的 `t()` 会让 oxlint 失败：这样的句子用 `msg()` 标记，在显示的地方再翻译。用户输入的内容不翻译。详情见 `docs/rules/translations.md`。

## 部署

需要生成 PDF 的服务器，PHP 的 `memory_limit` 至少要 256M。PDF 视图用 Noto Sans SC（`resources/fonts/`，每个字重约 10.6 MB，SIL 开源字体许可）绘制所有文字，所以中文能正常打印；DOMPDF 要读完整字体才能裁出 PDF 用到的字，一次渲染的峰值约 120 到 130 MB，超过 PHP 默认的 128M。`phpunit.xml` 已为测试设为 256M。DOMPDF 把字体文件和字体度量放在 `storage/fonts`，该目录必须可写。

## 来源与许可

基于 `laravel/react-starter-kit` 的 `98d9b82` 提交，认证和设置代码已按本模板的架构重写。项目新增内容采用 [MIT 许可证](LICENSE)。Laravel、随模板提供的 Laravel 技能和 shadcn/ui 组件保留上游版权声明；安装的第三方依赖分别遵循各自许可证。
