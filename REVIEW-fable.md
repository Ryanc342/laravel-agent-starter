# 审查报告：laravel-agent-starter

审查日期：2026-11-22　审查对象：分支 `template-simplification`，提交 `b849d161` 加上工作区里全部未提交的改动
这一轮只做了审查，没有改动仓库里任何已有文件。本文件是唯一新建的文件，看完可以删除，不要提交进模版。

可信度标记：【已验证】= 我亲眼看到文件原文或亲自运行命令得到；【推断】= 根据证据推理；【未验证】= 没法验证，后面写了怎么验证。

---

## 1. 总评

1. **最新一版（CHANGELOG 里叫 "2.0.0 - unreleased"）整个都没有提交**：512 项改动只存在于你电脑的工作区里，没有进 git，也从没在 CI 上跑过；而且用模版新建的项目在第一次 `git commit` 之前，所有 hook 都不工作，"先批准计划再写代码"这道门是关着的。
2. **有几道写着"机器保证"的门其实是虚的**：浏览器巡检只走一份写死的网址清单（新页面不会被检查），截图对比只在你的 Mac 上跑、基线文件没进 git，"每个决定都有测试"只看测试的名字（空测试也算），规则文件和检查脚本本身可以被 AI 不经批准改掉。
3. **150 条规则里有 57 条只靠 AI 审查员保证，而审查员用的是最弱的模型（haiku）**；你自己的计划记录里，连续 7 轮都是 "haiku clean; opus 1 P0"（haiku 说没问题，opus 找到了严重问题）。
4. 2.0 的方向本身是对的：给 AI 的指令从 1.3.0 的约 357 KB 减到约 103 KB，每条规则都写明了由谁保证，重复劳动收进了有测试的"零件"。**不建议推倒重来。**
5. 我的方案一句话：**先把成果提交并让 CI 验证，再补上虚的门、把能用机器查的规则交给机器、把审查员换成更强的模型；流程保持一条线不变，只让每一步更便宜、更真。**

---

## 2. 运行记录

### 2.1 版本表

来源：`composer.lock`、`package-lock.json`（仓库实际锁定的版本），以及本机命令输出。【已验证】

| 项目 | 版本 | 备注 |
| --- | --- | --- |
| PHP（本机） | 8.4.20 | `composer.json` 要求 `^8.4.1` |
| Laravel (`laravel/framework`) | 13.43.0 | |
| Inertia | `inertiajs/inertia-laravel` 3.6.0，`@inertiajs/react` 3.10.0 | |
| React | 19.3.1 | |
| Tailwind CSS | 4.3.3 | CSS 写法（`@theme`），没有 `tailwind.config.js` |
| Vite | `vite-plus` 1.4.0（`vite` 是 `@voidzero-dev/vite-plus-core@1.4.0` 的别名） | 命令是 `vp` |
| TypeScript | 5.9.3 | |
| oxlint / Vitest | 1.89.0 / 5.0.5 | 都随 vite-plus 提供 |
| Pest / PHPUnit | 4.7.8 / 12.5.33 | Pest 5 已发布，未升级 |
| pest-plugin-browser / Playwright | 4.3.1 / 1.65.1（写死） | |
| Larastan / PHPStan | 3.13.2 / 2.3.4（level 7） | |
| spaze/phpstan-disallowed-calls | 4.15.0 | 分层规则靠它 |
| Laravel Boost / laravel/mcp | 2.10.4 / 1.1.2 | `boost.json`：guidelines 关闭，只装了 1 个 skill |
| Fortify / laravel/passkeys | 1.41.0 / 0.2.1 | |
| Wayfinder | 0.1.23（Vite 插件 0.1.11） | 0.x 版本 |
| Pint / laravel/pao | 1.32.1 / 1.1.5 | pao 让命令输出变成给 AI 看的 JSON |
| spatie/laravel-typescript-transformer | 3.3.0 | 生成前端类型 |
| 本机工具 | Node 26.4.0，npm 11.17.0，Composer 2.9.7，Laravel Installer 5.32.0，Claude Code 2.1.330，MySQL 在运行 | `copier` 未安装 |

### 2.2 第 2 步运行的命令

全部是只读命令。每跑完一批都用 `git status --porcelain` 和开始时的快照逐行对比，结果一致。【已验证】

| 命令 | 结果 | 关键输出 |
| --- | --- | --- |
| `composer validate --no-check-publish` | 通过 | `./composer.json is valid` |
| `composer audit` | 通过 | `No security vulnerability advisories found.` |
| `npm audit` | 通过 | `found 0 vulnerabilities` |
| `vendor/bin/pint --parallel --test`（只检查不修复） | 通过 | `{"tool":"pint","result":"passed"}` |
| `vendor/bin/phpstan analyse --memory-limit=1G` | 通过 | `{"tool":"phpstan","result":"passed","errors":0}` |
| `npm run check`（`vp check`，格式 + oxlint） | 通过 | `All 267 files are correctly formatted`，`no warnings or lint errors in 259 files` |
| `npm run types:check`（`tsc --noEmit`） | 通过 | 无输出 |
| `npx vp test run`（Vitest） | 通过 | `74 passed (74)` 个文件，`400 passed (400)` 个测试，9.4 秒 |
| `php artisan test --compact --testsuite=Unit,Feature,Structure` | 通过 | 344 个测试，1484 个断言，11.3 秒 |
| `php vendor/bin/pest -c template/phpunit.xml --test-directory=template/tests --compact` | 通过 | 170 个测试，493 个断言，67 秒（只写系统临时目录） |
| `composer outdated --direct` / `npm outdated` | 仅查看 | 有新版本可升（见发现 19），无安全公告 |
| `php scripts/workflow.php status` / `fingerprint` | 仅查看 | `No plan is open...`；指纹 `8609fde1…`，耗时 0.156 秒 |

**没有运行、等你回复"继续"的命令**（它们会写文件）：

| 命令 | 会写到哪里 |
| --- | --- |
| `npm run build` | `public/build/`（已被 git 忽略）；同时重新生成 `resources/js/routes`、`actions`、`wayfinder`（已被忽略） |
| `php artisan test --testsuite=Browser`（浏览器测试） | 需要先 build；会写 `tests/Browser/Screenshots/`（已忽略），缺基线时还会往 `tests/.pest/snapshots/` 里新建文件（这个目录目前**未跟踪也未忽略**） |
| `composer check` / `composer check:static` | 里面的 `scripts/check-generated-types.php` 会**原地重写** `resources/js/types/generated.d.ts`；`scripts/check.php` 还会写 `.git/ezdev/passed` |

间接证据【推断】：`.git/ezdev/passed` 文件写于今天 19:35:09，内容 `8609fde196aa5c67175e9095860502d3cdc0992c`，和我运行 `php scripts/workflow.php fingerprint` 得到的当前代码指纹完全相同。按 `scripts/check-process.php:29`，这个文件只有在整套 `composer check` 全部通过后才会写入。所以很可能今天 19:35 有人（不是我）在这份代码上跑通过完整检查。它可以被手工伪造（见发现 7），所以只算推断。

需要说明的副作用：仓库的 hook 在我每次运行命令时都会自动执行，它们往 `.git/ezdev/`（不属于仓库文件）写了几个状态文件。受 git 管理的文件没有任何变化。

### 2.3 git 状态

- 开始：`git rev-parse HEAD` = `b849d16184ea502214250eff64cd52386f7b2b9b`；`git status --short | wc -l` = 512（239 个修改、189 个删除、84 个未跟踪条目，展开后是 195 个未跟踪文件）。
- 结束：见文末"附录 A"（完整输出和对比结果）。

### 2.4 "最新一版"指的是什么

- `main` / `origin/main`（`f808bd4a`）= 已发布的 **1.3.0**（2026-11-18）。
- 当前分支 `template-simplification` 比 `main` 多 123 个提交，HEAD = **1.6.0**（2026-11-20），只在本机，没有推送（`git ls-remote --heads origin` 只有 `main`）。
- **工作区里未提交的 512 项改动 = CHANGELOG 里的 "2.0.0 - unreleased"**，文件修改时间集中在今天 18:05–19:09。这就是你说的"最新一版"。因为它没有任何提交，**无法列出 commit**；改动范围是 428 个已跟踪文件（6530 行新增、19763 行删除）加 195 个新文件。逐组评估见第 3 节。

---

## 3. 最新一版（未提交的 2.0.0）改动的评估

| # | 改动 | 结论 | 理由和证据 |
| --- | --- | --- | --- |
| 1 | 用 `.claude/rules/skeleton.md` + 49 张卡片替换原来的 12 个规则文件、`golden-principles.md`、`ARCHITECTURE.md`、`DESIGN.md`、`docs/design/`、`docs/prompts/` | **保留**，按发现 5、8 修改 | 指令总量：`main` 约 357 KB → HEAD 约 200 KB → 现在约 103 KB【已验证，`git ls-tree -l` 与 `wc -c`】。每条规则标了保证人，这是全仓库最好的设计 |
| 2 | 用 `scripts/workflow.php`（6 个 hook）替换 finish-check、check:milestone、screen-round 等脚本 | **保留**，按发现 2、7、9 修改 | 脚本从 12 个减到 6 个；一个工具管住步骤；自带 65 个测试 |
| 3 | 新的计划格式（Purpose / Questions / Decisions / Screens，测试用 `<plan>.D1` 命名） | **保留**，按发现 6 修改 | 用业务语言批准、每个决定可追溯到测试，对不懂代码的人有用；但目前只核对名字 |
| 4 | "操作零件"：`Operation`、`UserlessOperation`、`Route::operation/listing/record/download/webhook`、`OperationJob`、`StoredFile`、`OutsideCall` | **保留**，注意发现 11 | 权限 → 输入 → 找记录 → 事务加锁 → 业务拒绝 → 写入的顺序写死在 `app/Actions/OperationSteps.php:140-185`，AI 想漏也漏不了；`tests/Feature/Parts` 有 66 个测试。偏离 Laravel 常规写法，但换来的是"结构保证"，值得 |
| 5 | 前端类型由后端 Resource 生成（`generated.d.ts`） | **保留** | 一份数据定义，两边不会写岔 |
| 6 | `/style` 页面成为唯一设计参考，大量共享组件 | **保留 `/style`**；演示专用组件见问题 4 | 可编译、有测试的参考比文档可靠；但 60 个共享组件里 29 个只有演示在用 |
| 7 | 浏览器测试（pest-plugin-browser）取代 AI 自己开浏览器（screen-round、Playwright MCP） | **保留**，按发现 5 修改 | 少一个 MCP、少一轮"看屏幕"，方向对；但"访问每个页面"现在不成立 |
| 8 | 审查改为只用 `reviewer-haiku` 分包审查，删除 `reviewer-opus` | **修改（部分回退）** | 见发现 4：你自己的记录显示 haiku 漏判 |
| 9 | 删除双语（`lang/`、翻译工具、语言切换、`users.locale`） | **需要你回答**（问题 2），默认保持删除 | 这取决于你的项目要不要中文界面；发布后再加回来的成本比现在高得多 |
| 10 | 删除 PDF（dompdf、spatie/laravel-pdf、中文字体） | **保留删除** | 不是每个项目都要，需要时在那个项目里加 |
| 11 | 删除参考功能 `docs/reference/`（60 个文件）和 `composer check:reference` | **保留删除**，按发现 12 补上"例子指路" | 副本需要专门检查才不会走样，删掉是减负；但 AI 现在没有被告知去哪里找可照抄的例子 |
| 12 | 删除 `README.zh-CN.md` | **保留删除** | 两份说明书会不同步；需要时让 AI 用中文解释 |
| 13 | 用户、通知、passkey 的主键改为 UUID（直接改了已有的 migration） | **保留** | 新项目从零开始没有影响；如果你有已上线的 1.x 项目，见问题 3 |
| 14 | `composer check` 成为唯一检查命令 | **保留** | 一个入口，CI 跑同一个命令 |
| 15 | 旧代码的问题不顺手修，记入 `tech-debt.md`，满 10 条给清理计划草稿 | **保留** | 防止改动范围失控 |
| 16 | `.claude/settings.json`：禁用 `AskUserQuestion`、`Workflow`，子代理在前台运行 | **保留**，按发现 10 增加 allow 清单 | 和"问题一次性写进计划"一致 |
| 17 | `.mcp.json` 只剩 `laravel-boost` | **保留** | 我在本次会话里实际调用成功（返回了数据库连接列表）【已验证】 |
| 18 | `copier.yml` + `docs/workflow.md` 的升级流程 | **保留**，按发现 9 修改 | 文档写的步骤顺序和 hook 冲突；本机没装 `copier`，整条升级路径【未验证】 |
| 19 | 已提交但未发布的 1.4–1.6 工作（123 个提交） | **不单独发布** | 2.0 已经把其中大部分又删掉或重写了 |

---

## 4. 发现列表

### 4.0 逐项审查结论（对应任务里的 A–I）

| 项 | 状态 | 检查了什么 | 结论 |
| --- | --- | --- | --- |
| A 目的契合度 | **严重** | `README.md`、`scripts/setup.php`、`composer.json`、`.gitattributes`、`copier.yml`、Laravel Installer 源码 | 2.0 没发布，照 README 装到的是 1.3.0；新项目首次提交前 hook 不生效（发现 1、2、3）；有模版专用内容残留（发现 13）。2.0 的安装流程没有实际跑过【未验证】，验证办法：开 PR 让 CI 跑 `template/scripts/check-installer` |
| B AI 指令层 | **有问题** | `AGENTS.md`、`CLAUDE.md`、`skeleton.md`、49 张卡片、`settings.json`、2 个子代理、1 个 skill、`.mcp.json`、`boost.json`、`workflow.php` 全文 | 见发现 4–10、12、15。没问题的部分：`CLAUDE.md` 只有一行 `@AGENTS.md`，不是工具生成的；`boost.json` 的 `"guidelines": false` 加上 `vendor/laravel/boost/src/Console/UpdateCommand.php:39` 保证 `boost:update` 不会覆盖它【已验证】；`AGENTS.md` 47 行；hook 每次约 0.16 秒，没有发现死循环 |
| C 工作流简化 | **有问题** | `docs/workflow.md`、`docs/PLANS.md`、`workflow.php` | 推演见 5.2 |
| D 前端约定 | **有问题** | 13 张前端卡片、`lint/oxlint/ezdev.js`、`vite.config.ts`、`resources/js/**`（212 个文件，由子代理通读，关键结论我复查） | 逐条结论见 4.20 |
| E 测试与质量门 | **有问题** | `tests/**`（188 个文件）、`template/tests/**`、`phpunit.xml`、`.github/workflows/tests.yml` | 见发现 5、6、17。没有发现任何跳过测试或忽略注释（全仓库搜索为零） |
| F 安全 | **没问题** | `.gitignore`、`.env.example`、`config/app.php`、`config/session.php`、`bootstrap/app.php`、webhook 和下载零件、密钥扫描、依赖审计 | `.env` 未入库；没有密钥；`'debug' => (bool) env('APP_DEBUG', false)`（`config/app.php:42`）；审计 0 漏洞；`/style` 只在本地环境可访问且有测试；CI 的 Action 用提交哈希锁定、只读权限；`.npmrc` 禁止安装脚本。hook 不执行危险命令 |
| G 依赖 | **没问题**（有建议） | 两个 lock 文件、`composer outdated`、`npm outdated` | 见发现 19 |
| H 代码与结构 | **有问题**（轻） | 后端核心零件我通读，其余抽查 | 有意偏离 Laravel 常规（全部写操作走 POST + 一个通用控制器），是换取结构保证的取舍，保留；问题在未被真实功能使用的零件和 3 处 "(prototype)" 标注（发现 11）、死引用（发现 14） |
| I 最新一版改动 | 见第 3 节 | | |

---

### 发现 1：最新一版整个没有提交，也没在 CI 上验证过

- **严重程度**：严重
- **大白话**：你这几天做的全部内容只存在于工作区里。一条误操作的 git 命令（或者 AI 被要求"把文件恢复原样"）就可能把它抹掉，而且没有历史可找回。它也从来没在 GitHub 的检查里跑过。
- **证据**：`git status --short | wc -l` → `512`；未跟踪（从未提交）的包括 `scripts/workflow.php`、`.claude/rules/skeleton.md`、`.claude/rules/skeleton/`、`app/Actions/Operation.php`、`tests/.pest/`（56 张截图基线，10 MB）、`resources/js/types/generated.d.ts`。`git ls-remote --heads origin` → 只有 `refs/heads/main`。`.github/workflows/tests.yml:3-7` 只在推送到 `main` 和 pull request 时运行。
- **可信度**：【已验证】（独立复核确认）
- **建议改法**：先把文件夹整个复制一份做备份；然后在当前分支提交全部改动（不含本报告）；再开一个到 `main` 的 pull request（先不合并）让 CI 跑。只推分支不开 PR 是不会触发 CI 的。
- **成本 / 风险**：小 / 几乎没有。提交可以用 `git reset --soft HEAD~1` 原样退回。

### 发现 2：新项目在第一次提交之前，工作流完全不生效；流程里也没有任何地方要求提交

- **严重程度**：严重
- **大白话**：用模版新建一个项目后，AI 可以不写计划、不等你说"开始"就直接改代码，因为所有 hook 在"还没有任何提交"的仓库里什么都不做。而安装脚本只建了仓库、没有做第一次提交。之后如果一直不提交，下一个计划的审查范围还会把上一个计划的改动也算进去。这个仓库现在积了 512 项未提交改动，就是这个问题的现场。
- **证据**：
  - `scripts/workflow.php:125-137`：`// Outside a git repository, or before its first commit, there is nothing to keep` → `return '';`
  - `scripts/setup.php:92`：只执行 `git init --initial-branch=main`，没有 commit。
  - Laravel Installer（`~/.composer/vendor/laravel/installer/src/NewCommand.php:735`、`1273`）：只有带 `--git` 或 `--github` 才会提交；`README.md:33` 的命令两个都没带。
  - `template/tests/Hooks/WorkflowTest.php:946`：`'outside a git repository, or before its first commit, the tool says nothing in any language'`（这是有意设计的行为）。
  - `grep -n -i commit AGENTS.md docs/workflow.md docs/PLANS.md`：没有任何一处让 AI 或你去提交。
  - `scripts/workflow.php:655`（批准时记下当时的 HEAD 作为起点）和 `:925`（审查范围 = 从起点到现在的全部差异）。
- **可信度**：代码行为【已验证】；"下一个计划会连带审查上一个计划的改动"【推断】，依据是上面两行代码。
- **建议改法**：
  1. `scripts/setup.php`：`git init` 之后做第一次提交；提交失败（比如没配 git 用户名）就明确报错。
  2. `scripts/workflow.php`：仓库没有提交时不要沉默，SessionStart 说明"先做第一次提交"，PreToolUse 照样拒绝写代码。
  3. 说"开始"时，如果工作区还有上一个计划未提交的代码改动，就拦住并提示先提交。
  4. `docs/workflow.md` 的 Report 一节和 `report` 命令的最后一行加一句：你接受报告后，把这次改动提交成一个提交。
- **成本 / 风险**：中 / 要改 `WorkflowTest.php:946` 这个已有断言（需要在计划里写成一个决定）；安装脚本的测试 `template/tests/Installation/InitializeProjectTest.php` 要补用例。

### 发现 3：要发布的 "2.0.0" 和本机已有的旧标签重名

- **严重程度**：严重（一旦推送标签就会出错）
- **大白话**：你电脑上有 21 个旧的 `v2.0.0`–`v2.13.0` 标签（十一月初的旧版本），还没推到 GitHub。现在这一版也打算叫 2.0.0。到时候打标签会失败；如果有人执行了"推送所有标签"，GitHub 和 Packagist 上最新的稳定版会变成旧的 v2.13.0，别人用模版新建项目就会装到旧代码。
- **证据**：`git tag` → `v2.0.0 … v2.13.0`（日期 2026-11-01 至 11-04，说明是 `Ezdev Template v2.x`）；`git ls-remote --tags origin` → 只有 `v1.0.0`–`v1.3.0`。`template/CHANGELOG.md:5` 是 `## [2.0.0] - unreleased`，`:578` 又有一个 `## [2.0.0] - 2026-10-31`。`README.md:60`、`:128` 让人使用 `v2.0.0`。两个旧标签都是 `main` 的祖先（`git merge-base --is-ancestor`），改名不会丢历史。
- **可信度**：【已验证】；"Packagist 会把 v2.13.0 当最新"【推断】。
- **建议改法**：把本机旧标签改名（例如 `ezdev-v2.0.0`），不删；把 CHANGELOG 里旧的 2.0.0–2.13.0 小节标题改成 `Ezdev 2.x`。这样新版本可以放心叫 2.0.0。
- **成本 / 风险**：小 / 无（标签不在远端，随时能改回去）。

### 发现 4：57 条规则只靠 AI 审查，而审查员是最弱的模型，你自己的记录显示它会漏

- **严重程度**：重要
- **大白话**：超过三分之一的规则（包括权限、加锁、数据写入位置这类重要的）没有任何程序检查，只靠一个 haiku 审查员读代码判断。它说"没问题"并不等于没问题。另外，审查员误报时没有正规的处理办法，AI 实际能做的是原样重跑审查、直到某一轮刚好 0 条。
- **证据**：
  - 规则统计（脚本按 `workflow.php` 的解析方式统计）：150 条里 `reviewer` 57、`machine` 31、`part` 25、`test` 2、`note` 35。【已验证】
  - `.claude/agents/reviewer-haiku.md:3`：`model: haiku`；`reviewer-opus.md` 在这一版被删除。
  - `template/plans/template-simplification.md` 第 167–173 行，连续 7 轮（round 4–10）的记录都是 `haiku clean; opus 1 P0`，例如 `Review round 6 ... haiku clean; opus 1 P0 (a project's DB_URL would have sent the copies' migrations to the project's own database...)`。【已验证】
  - `docs/workflow.md:22`：`Fix every finding ... Never edit a pack or a reviewer's answer.`；`scripts/workflow.php:973-1000`：每张卡片必须有一轮 findings=0 才算通过；全仓库没有"申诉"机制。独立复核补充：`reviewRound()` 在代码不变时可以无限次开下一轮。
  - 典型任务的审查量：一个"联系我们"页面会碰到 28 张卡片、39 条审查规则 → 每轮 4 包 + 1 个全局包 = 5 个审查子代理。【已验证，脚本模拟】
- **可信度**："haiku 会漏"【已验证】（来自你自己的记录，但那是旧审查体系下的记录）；"换成 sonnet 就够"【未验证】，验证办法：下一个真实计划的第一轮，同一批包分别用 haiku 和 sonnet 各跑一次，对比结果。
- **建议改法**：
  1. 子代理改名为 `reviewer`，`model: sonnet`。要同步改的地方：`.claude/settings.json:69`、`scripts/workflow.php:1070,1138`、`docs/workflow.md:22`、`AGENTS.md:43`、`README.md:69,90`、`template/tests/Hooks/WorkflowTest.php`（多处，其中 1062–1065 断言了 `model: haiku`）。
  2. 加一条正规的申诉通道：AI 认为是误报时，引用该规则 Check 行里的 "Not:" 条款写明理由，由一次新的审查重新判断；仍然成立就必须改。
  3. 报告里写明"在代码没变的情况下重审了几次"，让重抽变得可见。
  4. 同时做发现 8 的"规则改机器查"，从源头减少要审的规则。
- **成本 / 风险**：中 / 每次审查的费用上升；要改工具和它的测试。

### 发现 5：几条标着"机器保证"或"零件保证"的规则，实际上保证不了

- **严重程度**：重要
- **大白话**：规则旁边写着"有程序把关"，AI 和你都会因此放心，但其中几条程序其实管不到。
- **证据**【均已验证，独立复核确认】：
  - **PAGE-6**（"浏览器测试访问每个页面"，标 `machine`）：`tests/Browser/PagesTest.php:78-97` 是写死的网址清单。`resources/js/pages/auth/two-factor-challenge.tsx` 是真实页面（`app/Providers/FortifyServiceProvider.php:77`），巡检从没访问过。新加页面不加进清单，没有任何检查会失败。
  - **PAGE-8**（"页面和你批准的截图一致"，标 `test`）：`PagesTest.php:61` 只在 `PHP_OS_FAMILY === 'Darwin'` 时比对，CI 是 `ubuntu-latest`，所以 CI 从不比对；规则说基线在 `tests/Browser`，实际在 `tests/.pest/snapshots/Browser/`（`workflow.php:65`）；这 56 个基线目前未进 git。
  - **COMP-7**（"只有 SubmitButton 能提交表单"，标 `machine`）：`lint/oxlint/ezdev.js:171-207` 只认写死的 `type="submit"` 字面量和没写 type 的原生 `<button>`；`type={条件 ? 'submit' : 'button'}`、展开属性、在普通按钮的 onClick 里 `router.post()` 都查不到。
  - **PAGE-3 / PAGE-4 / PAGE-5**（标 `part`）：零件存在，但没有任何检查强制使用它们。例如 `resources/js/components/shared/error-content.tsx:52` 用普通按钮发请求。
  - **TEST-2**：规则说"每个屏幕有一个同名的**浏览器**测试"，但 `workflow.php:566-580` 接受任何名字里带 `<plan>.S1` 的测试，包括一个 Vitest 测试。
- **建议改法**：
  - PAGE-6：把巡检清单改成"页面文件 → 怎么访问它"的对照表，加一个测试要求 `resources/js/pages/` 下每个文件都在表里（需要准备数据的页面写明准备步骤）。
  - PAGE-8：基线提交进 git；规则里改对路径，并如实写"只在作者的 Mac 上比对"。
  - COMP-7、PAGE-3/4/5：要么把规则文字改成 lint 实际检查的范围、其余部分改标 `reviewer` 并补 Check 行，要么补 lint（见发现 8）。
  - TEST-2：屏幕（S 开头）只接受 `tests/Browser/` 下的测试。
- **成本 / 风险**：中 / 巡检改造要给 two-factor-challenge 页面准备登录中间状态；基线进 git 会让仓库增大约 10 MB。

### 发现 6："每个决定都有测试"这道门只看名字

- **严重程度**：重要
- **大白话**：AI 只要写一个名字里带 `contact.D1` 的空测试，工具就认为这个决定"已建好"。空测试在现在的配置下还不会让检查失败。真正的把关只剩审查员那一条规则（TEST-3）。
- **证据**：`scripts/workflow.php:574` 只对测试的名字或分组做文本匹配；模版自己的测试把空测试当作合格样例：`template/tests/Hooks/WorkflowTest.php:130` → `test('only the customer accepts', function () {})->group('quotes.D1');`。`phpunit.xml:6-7` 只有 `failOnSkipped` 和 `failOnIncomplete`；`vendor/phpunit/phpunit/phpunit.xsd:191` 里 `failOnRisky` 默认 `false`。
- **可信度**：工具行为【已验证】；"无断言的 Pest 测试会让 `composer check` 照样通过"【推断】（我在仓库外做实验没跑成）。验证办法：临时加一个空测试跑 `php artisan test`，看退出码。
- **建议改法**：`phpunit.xml` 加 `failOnRisky="true"`，并在 `tests/Structure/CheckSettings.php` 的 `pestSuites()` 里把它钉住（防止被改回去）。它挡不住 `expect(true)->toBeTrue()` 这种假断言，那部分仍由 TEST-3 审查。
- **成本 / 风险**：小 / 可能让现有的个别无断言测试变红，需要补断言。

### 发现 7：规则文件和检查工具本身没有被门保护

- **严重程度**：重要
- **大白话**：AI 不能在你批准前改代码，但可以改规则本身；批准之后还可以改"检查都跑哪几步"的脚本，而你不会收到提示。"检查通过"和"审查通过"这两个记录也都可以用一条命令伪造。在用模版做出来的项目里，工作流工具自己没有任何测试守着。
- **证据**【均已验证】：
  - `scripts/workflow.php:1543-1546`：`return ! str_ends_with($path, '.md') && ! str_starts_with($path, 'docs/');` —— 所有 `.md` 都不算代码，所以 `.claude/rules/**`、`AGENTS.md`、`.claude/agents/reviewer-haiku.md` 批准前可改，也不计入代码指纹。
  - `scripts/workflow.php:77` 的 `CHECK_FILES` 不含 `scripts/`、`.claude/`、`composer.json`、`.github/`。`scripts/check.php:8-16` 才是真正的检查步骤清单，没有任何东西钉住它。
  - `scripts/workflow.php:414-432`：`php scripts/workflow.php passed $(php scripts/workflow.php fingerprint)` 就能记录一次"检查通过"。`:807-824`：任何一次 reviewer 子代理只要最后一行是 `RESULT pack=… findings=0` 就被记为通过，不管它实际收到了什么指令。
  - `.gitattributes:10`、`copier.yml:17`：`template/`（工具的全部测试所在）不进项目。
- **说明**：这个工具是"引导"，不是"保险箱"，AI 有命令行就总能绕过。真正的底线是 CI。所以这里的目标是让越界**被你看见**，不是做到绝对防伪。
- **建议改法**：`CHECK_FILES` 加上 `.claude/`、`scripts/`、`AGENTS.md`、`composer.json`、`package.json`、`.github/`；`isCodePath()` 把 `.claude/` 和 `AGENTS.md` 算作受门保护的路径；`passed` 命令要求由 `scripts/check.php` 调用（例如通过一次性口令文件）。
- **成本 / 风险**：小 / 改规则卡片以后也要先有批准的计划（这正是 README 第 109 行承诺的）。

### 发现 8：模版自己的代码违反自己的规则；其中不少规则本可以由机器检查

- **严重程度**：重要
- **大白话**：AI 学写法主要靠照着现有代码抄。现有代码自己就违规，AI 会跟着违规，审查员再把它记成"旧债"。而这些规则大多是"某个位置不许出现某种写法"，写一条 lint 就能永久管住，不需要每次靠 AI 读。
- **证据**【已验证，独立复核确认】：
  - **PAGE-9**（页面和布局的 className 只能放布局类）：`resources/js/layouts/auth/auth-simple-layout.tsx:8` `bg-background`、`:14` `font-medium`、`:16` `rounded-md`、`:17` `text-foreground`；`layouts/public-layout.tsx:26` `bg-background text-foreground`；`layouts/error-layout.tsx:10`。14 个真实页面本身是干净的。
  - **PAGE-7**（按钮只写一个动词）：`resources/js/components/notifications/mark-all-read-button.tsx:27` → `Mark all as read`。
  - **ACT-14**（应用代码不派发事件）：`app/Actions/Users/DeletePasskey.php:33` → `PasskeyDeleted::dispatch($user, $subject);`。
  - **COMP-10**（有状态的组件在 `tests/js/` 同路径有测试）：14 个文件没有同路径测试（它们被别的名字的测试覆盖），规则的说法和模版的实际做法不一致。
  - **HTTP>WEB-4**（手写类型和后端逐字段一致）：`app/Http/Controllers/Settings/SecurityController.php:26-28` 总是发送的字段，在 `resources/js/components/users/manage-passkeys.tsx:8-9` 被写成可选，后面再用 `?? []` 兜底。
  - 可争议的一条：**PAGE-3**，`resources/js/components/users/manage-two-factor.tsx:54-58` 关闭两步验证没有确认框，而同一页的"重新生成恢复码"用了 ConfirmButton。
- **建议改法**：先修上面这些违规。然后把下列规则从 `reviewer` 改成 `machine`，每条按现有惯例在 `template/tests/Lint/` 里配"违规样例被拦、合规样例放行"两个方向的证明：

  | 规则 | 机器检查办法 | 现有代码能否直接通过 |
  | --- | --- | --- |
  | COMP-5 | oxlint：`ui/input`、`ui/label`、`ui/textarea`、`ui/select` 只能被 `components/shared` 引入 | 能（需放行 1 处 `ui/input-otp`） |
  | COMP-6 | oxlint：`size="icon"` 只能出现在 `icon-button.tsx` | 能 |
  | PAGE>COMP-2 | oxlint：页面和功能组件里禁用原生 `table`、`dl`、`button`、`input`、`label`、`form` | 页面能；`components/style` 的演示要先改 |
  | WEB>HTTP-4 | oxlint：禁 `fetch`、`XMLHttpRequest`、`axios`；禁以 `/` 开头的站内地址字面量 | 能 |
  | PAGE-9、PAGE>COMP-3 | oxlint：页面和布局里的 className **禁用清单**（`bg-`、颜色和字号的 `text-`、`font-`、`border`、`rounded`、`shadow`、`divide-`、`animate-`、`style=`），用规则自己列的 Break 清单 | 修完 7 行布局代码后能 |
  | PAGE-10（时钟部分） | oxlint：禁无参数 `new Date()` 和 `Date.now()` | 能 |
  | MODEL-3 | 结构测试：每个 Model 有对应 Factory | 能 |
  | MIGR-1 | `workflow.php`：计划起点时已存在的 migration 不得改动（模版同步计划除外） | — |
  | ACT-14 | PHPStan：禁 `event()`、`Event::listen`、`::dispatch`（事件类）、`observe` | 修完 `DeletePasskey` 后能 |

  独立复核提醒：ROUTE-2 不能直接改标 `machine`，因为 `tests/Feature/PartRoutesTest.php:92-96` 会放过用闭包写的 `Route::post`；CTRL>MODEL-1 改机器查之前要先用样例证明 PHPStan 看得见 Eloquent 转发的 `where`。这两条先留给审查。
- **成本 / 风险**：中到大（约 9 条检查，每条带两个方向的样例）/ lint 误报不能加忽略注释（ALL-10 禁止），所以每条都必须先在现有代码上跑通再启用；PAGE-9 用禁用清单比用允许清单误报少。

### 发现 9：升级流程的写法和 hook 互相冲突

- **严重程度**：重要
- **大白话**：说明书让 AI 在你批准之前先运行模版同步命令，可这条命令会改代码，hook 马上会要求 AI"把文件改回去"。照着写的步骤走不通。
- **证据**：`docs/workflow.md:34-36`：`Before you run it: ... Run copier update --defaults --vcs-ref=<tag>`；`scripts/workflow.php:712-730`：批准前命令改了代码 → `Put these files back as they were`。【已验证，读了两处原文】整条升级路径没有实际跑过（本机 `which copier` → not found）【未验证】，验证办法：用 v1.3.0 建一个临时项目，按流程升级一次。
- **建议改法**：把顺序改成用现有机制能走通的两段：先写一个最小的升级计划（"是否采用版本 X"）→ 你说"开始" → 运行 `copier update` → 把每个冲突作为新问题写进计划（按 `docs/PLANS.md:15`，改计划会让批准失效）→ 你回答后再说一次"开始"。只需要改 `docs/workflow.md` 的 Upgrades 一节和 `README.md:128`。
- **成本 / 风险**：小 / 无。

### 发现 10：没有预先放行流程自己要用的命令

- **严重程度**：重要
- **大白话**：流程要求 AI 反复运行 `composer check`、单项测试、审查命令。配置里没有预先批准其中任何一条，所以在默认模式下每一条都会弹窗问你；你不懂代码，只能一路点"同意"，或者干脆开全部放行。
- **证据**：`.claude/settings.json:6-8` 只有 `"deny": ["AskUserQuestion", "Workflow"]`，没有 `allow`【已验证】。Claude Code 官方文档（https://code.claude.com/docs/en/permissions.md）：默认模式下除了少数只读命令，Bash 命令都要批准；可以用 `Bash(composer check:*)` 这类规则预先放行。【已对照文档】你实际用的是哪种权限模式我不知道，所以"频繁弹窗"是【推断】。
- **建议改法**：在 `.claude/settings.json` 加一份很窄的 allow 清单：`composer check`、`composer check:static`、`composer lint`、`php artisan test`、`npx vp test run`、`npm run check:fix`、`php scripts/workflow.php status`、`... review`、`... report`。**不要**放行整个 `php scripts/workflow.php`（会顺带放行伪造用的 `passed`），也不要放行整个 `php artisan`。
- **成本 / 风险**：小 / 清单写宽了会放行危险命令，所以逐条列出。

### 发现 11：很多零件和组件从没被真实功能用过，却随每个新项目一起发出去

- **严重程度**：重要（维护成本）
- **大白话**：模版里有一半的共享组件只在演示页上出现；后端的"记录页、下载、webhook、后台任务、文件、外部服务"六个零件，模版自己的功能一个都没用。它们有测试，但没经过真实使用，代码里还留着 3 处"原型"字样。每个新项目都要带着它们升级和维护。
- **证据**【已验证，独立复核确认】：
  - `resources/js/components/shared` 的 60 个文件里 29 个（2,880 行）只被 `/style` 演示引用，包括规则点名的零件：`export-button`、`date-picker`、`textarea-field`、`bulk-action-bar`、`detail-list`、`responsive-table`。`/style` 本身 38 个文件 4,245 行。`recharts` 和 `@dnd-kit/*` 只为演示组件服务。
  - `routes/*.php` 里没有任何 `Route::record()`、`Route::download()`、`Route::webhook()`；没有应用代码派发任务。`RecordController`、`DownloadController`、`WebhookController`、`OperationJob`、`StoredFile`、`OutsideCall` 只被 `tests/Feature/Parts` 里的测试专用操作使用。
  - `app/Providers/AppServiceProvider.php:44` → `The route parts (prototype).`；`app/Http/Controllers/ListingController.php:22` → `The query part (prototype)`；`app/Models/Notification.php:87`。
  - 一个具体的局限【推断】：`app/Http/Controllers/WebhookController.php:39-41` 只支持 `sha256=<hex>` 这一种签名格式，没有防重放；Stripe 这类带时间戳的签名接不上。
- **建议改法**：后端零件**保留**（它们让一批规则变成结构保证，且有 66 个测试）；删掉 "(prototype)" 字样要等第一个真实项目用过之后。前端演示专用组件的去留见问题 4，我的默认是这一轮不动。
- **成本 / 风险**：现在不动则为零。

### 发现 12：卡片没有告诉 AI 去哪里找例子，唯一的 Boost skill 推荐了模版里行不通的写法

- **严重程度**：建议
- **大白话**：模版用的是自己的一套写法（操作零件），AI 的常识里没有。卡片只讲规则，不指向可照抄的例子，AI 每次都要自己搜。同时 Wayfinder 的 skill 把"从 `@/actions/...` 引入"标为首选，而模版里所有写操作共用一个控制器，这种引入方式拿不到对应的函数。
- **证据**：`grep -rn "Example\|see app/\|see routes/" .claude/rules` 只有 1 处无关结果。`.claude/skills/wayfinder-development/SKILL.md:32-33` 推荐 `@/actions/App/Http/Controllers/PostController`；模版手写前端从 `@/routes` 引入 55 次、从 `@/actions` 引入 1 次。【已验证】用错写法会被 `tsc` 拦住，所以代价是浪费来回，不是错误代码上线【推断】。
- **建议改法**：在 `act.md`、`route.md`、`page.md`、`web-to-http.md` 各加一行"例子"：写操作看 `app/Actions/Users/UpdateProfile.php` 和 `routes/settings.php:17`；列表页看 `routes/notifications.php:10`；上传和导入看 `tests/Feature/Parts/FilePartTest.php`；前端一律从 `@/routes/<名字>` 引入。不要改 skill 文件本身（它由 Boost 生成，会被覆盖）。
- **成本 / 风险**：小 / 无。

### 发现 13：新项目里会残留模版专用的内容

- **严重程度**：建议
- **证据**【已验证】：只有 `/template` 被排除（`.gitattributes:10`、`copier.yml:17`），`scripts/setup.php` 不改任何说明文件。所以新项目会带着：模版自己的 `README.md`、指向模版 GitHub 的 `CONTRIBUTING.md` 和 `SECURITY.md`、`composer.json` 里的名字 `ryanc342/laravel-agent-starter`、`AGENTS.md:1` 的标题 `Ezdev Template: agent guide`、`copier.yml`、`docs/exec-plans/tech-debt.md` 里模版的 4 条旧债。
- **建议改法**：`AGENTS.md` 标题改成不带模版名；`setup.php` 在新建项目时把 `README.md` 换成几行的项目说明、删掉 `CONTRIBUTING.md` 和 `SECURITY.md`；发布前把 4 条旧债修掉，让新项目从 0 条开始。`LICENSE` 保留。
- **成本 / 风险**：小 / 要给安装测试补用例。

### 发现 14：死引用和过期文字

- **严重程度**：建议
- **证据**【已验证】：`vite.config.ts:72` `'bootstrap/ssr/**'`、`:74` `'tailwind.config.js'`（都不存在）；`package.json:7` 有 `build:ssr` 但没有 `resources/js/ssr.tsx`；`tests/js/display-size.test.ts:10` 提到已删除的 `foundations.md`；`resources/js/types/resource.ts:3` 提到不存在的 `TaskResource`；`tests/Structure/ConfigTest.php:26` 写 "three suites"（实际 4 个）；`docs/workflow.md:17` 说"新页面要加进侧边栏"，没区分公开页面。
- **建议改法**：逐处删改。注意 `vite.config.ts` 是受保护的检查文件，改它要作为计划里的一个决定。
- **成本 / 风险**：小 / 无。

### 发现 15：10 张卡片没有任何规则内容；52 处规则副本没有"保持一致"的测试

- **严重程度**：建议
- **证据**【已验证】：卡片共 49 张。`act-to-model`、`app`、`comp-to-theme`、`conc-to-freq`、`conc`、`ext`、`files`、`route-to-ctrl`、`supp`、`wayf` 这 10 张只有一句 `note` 描述或只有链接。规则在 "Also applies here" 下被整句复制了 52 次；`grep -rn "Also applies" tests template/tests` 无结果，即没有测试保证副本和原文一致（独立复核确认目前 0 处不一致）。
- **重要更正**：我最初想把卡片从 49 张并成约 22 张、把副本换成只写编号。独立复核指出这会出问题：`workflow.php:862` 正是靠这些带 `reviewer` 标记的副本行来决定"哪些规则审哪些路径"，换成编号会让这些路径悄悄失去审查。**所以我撤回合并卡片的方案。**
- **建议改法**：加一个测试，要求每处副本和它的原文逐字相同（放在 `tests/Structure/`，这样项目里也有）。那 10 张空卡片可删可不删；要删就连同 `skeleton.md` 表格里的链接一起改。
- **成本 / 风险**：小 / 删卡片要确认 `WorkflowTest.php:408-409,438` 不依赖它们。

### 发现 16：一个"检查"会改文件；`.git/ezdev` 里有残留

- **严重程度**：建议
- **证据**【已验证】：`scripts/check-generated-types.php:15-28` 先重新生成 `generated.d.ts` 再比较，不一致就报错并留下新文件。`.git/ezdev/` 下有 59 个文件，包括 1.x 时代的 `review-ledger.jsonl`、`reviewer-refusals.jsonl`、45 个 `session-*.json`，以及今天留下的 3 个 `command-*.json`（每个 65 KB，命令失败时 PostToolUse 不会清理它们）。
- **建议改法**：类型检查保持现状也可以接受（报错后重跑即可，CI 上会正常失败），在 `AGENTS.md` 的命令说明里写明"这一步会更新文件"。旧状态文件可以手动删除（它们不在仓库里）；`workflow.php` 可以在 SessionStart 时清掉超过一天的 `command-*.json`。
- **成本 / 风险**：小 / 无。

### 发现 17：测试里的弱点

- **严重程度**：建议
- **证据**（子代理通读 188 个测试文件，下列我或独立复核核对过）：
  - `tests/Feature/Auth/PasswordResetTest.php:42`：把密码重置成 `'password'`，和工厂默认密码相同，只断言没有错误和跳转；HTTP 这一层即使什么都没存也会通过。（底层动作在 `tests/Feature/Users/PasswordTest.php:16-25` 有真实断言。）
  - 9 个只断言状态码的测试（如 `tests/Feature/ExampleTest.php:7`、`DashboardTest.php:66`）。
  - 3 个不可能失败的 Vitest 测试（如 `tests/js/components/users/two-factor-setup-step-controls.test.tsx:67`）。
  - `vite.config.ts:55` 的 lint 插件没有 `react`，所以没有 hooks 规则检查。
  - 行锁测试（`tests/Support/TestsRowLocks.php:35-44`）证明锁被持有，但不是两个请求真的并发。
  - 整个 PHP 测试（包括不需要数据库的结构测试）都要 MySQL 在运行（`tests/bootstrap.php:28-32`）。
- **建议改法**：修前两类；给 lint 加 `react` 插件（要作为计划决定，因为改了检查配置）。其余可接受。
- **成本 / 风险**：小 / 加 `react` 插件可能报出现有问题。

### 发现 18：README 说"短命令跟随 main 分支"，很可能不对

- **严重程度**：建议
- **证据**：`README.md:60`：`The short command follows the template's main branch`。Laravel Installer 实际执行的是 `composer create-project {$starterKit} "{$directory}" --stability=dev`（`NewCommand.php:635`），不带版本时 Composer 选的是最高版本号的标签，不是分支。【推断】（基于 Composer 的版本选择规则，我没有实际建项目验证）。验证办法：建一个临时项目后看 `composer.lock` 或 `vendor/composer/installed.json` 里记录的版本。
- **影响**：如果我的推断对，"合并到 main"不等于发布，"打标签"才是；这反而更安全。把 README 这句改成与事实一致即可。
- **成本 / 风险**：小 / 无。

### 发现 19：依赖状况

- **严重程度**：建议（目前没问题）
- **证据**【已验证】：两边审计都是 0 漏洞。有主版本可升：Pest 4.7.8 → 5.3.7（连同两个插件）、TypeScript 5.9.3 → 7.0.2、`lucide-react` 0.475 → 1.77、`@laravel/passkeys` 0.2.0 → 0.4.1、`vite-plus` 1.4.0 → 1.7.0（版本写死）。
- **以后升级最容易坏的地方**【推断】：① `vite-plus`（`vite` 用别名覆盖，格式化、lint、测试、构建全靠它一个包）；② `spaze/phpstan-disallowed-calls`（全部分层规则靠它）；③ Wayfinder 和 passkeys 都是 0.x；④ `pest-plugin-browser` 配写死的 Playwright 版本；⑤ Inertia 3 和 Fortify 的视图接口；⑥ Claude Code 的 hook 输入输出格式（`workflow.php` 依赖 `last_assistant_message`、`stop_hook_active` 等字段，目前和官方文档一致）。
- **建议改法**：发布 2.0 之前不升级。发布后走一次升级计划。

---

### 发现 20：Stop hook 会把别的时间做的改动算到当前对话头上（本次审查结束时当场发生）

- **严重程度**：重要
- **大白话**：我这一轮没有改任何代码，但在我准备结束时，hook 拦住我说"这次对话改了 16 个代码文件，却没有批准的计划"，要求我先写计划。这是误报。在一个积着大量未提交改动的仓库里，这种误报会让 AI 去写不该写的计划，判断力差一点的还可能去"恢复"你的文件。
- **证据**【已验证】：
  - hook 原话：`This conversation changed code (app/Http/Controllers/AllNotificationsReadController.php, app/Http/Controllers/LocaleController.php, app/Http/Controllers/NotificationController.php and 13 more) with no approved plan.`
  - 这 16 个路径我逐个对上了：13 个是早就删除的文件（`app/Http/Controllers/` 下 4 个旧控制器、`lang/` 下 9 个），3 个是 19:32:40–19:32:42 写入的文件（`ListingController.php`、`ListingPartTest.php`、`generated.d.ts`），都早于我的第一条命令（约 19:35）。
  - 我没改代码的证明：19:34 之后被写入的非忽略文件只有 `./REVIEW-fable.md`（`find -newermt`）；当前代码指纹 `8609fde1…` 和 19:35:09 记录的指纹完全相同。
  - 原因在 `scripts/workflow.php:1508-1519`：对已删除的文件，它看的是"最近一个还存在的上级文件夹"的修改时间。`lang/` 整个文件夹已经不在了，最近的上级就是仓库根目录；我按你的要求在根目录新建报告文件，根目录的修改时间变了，9 个 `lang/` 文件就全被算成"这次对话删的"。
  - "对话开始时间"取的是会话记录里的第一个时间戳（`:1464-1498`）。它早于 19:32:40，所以 `/clear` 之前或同一时段别处做的 3 处修改也被算了进来。具体是哪种情况我没有查会话记录【推断】。
- **我的处理**：没有照 hook 说的去写计划（这一轮你只允许新建报告文件），也没有恢复任何文件。hook 设计上只拦一次，我再次结束时你会看到它的一行提示，可以忽略。
- **建议改法**：不要用文件修改时间来猜。会话开始时（SessionStart 在 `/clear` 后也会触发）用工具已有的 `fingerprintFiles()` 存一份全部文件的快照，Stop 时和快照逐个比较。这样结果是精确的，删除的文件也能正确归属。
- **成本 / 风险**：小到中 / 要改 `template/tests/Hooks/WorkflowTest.php` 里 Stop 相关的用例（第 705–858 行）。

---

### 4.20 前端约定逐条结论（任务 D）

所有规则提到的文件、命令、测试都存在，除了 PAGE-8 的路径写错。版本上没有冲突：代码用到的 Inertia 3 接口在 `node_modules` 里都有，Tailwind 是 v4 的 CSS 写法。下表只列需要动的；没列的（PAGE-1、PAGE-2、COMP-1、COMP-2、COMP-8、COMP-9、LAYOUT-1、LAYOUT-3、LIB-1、THEME-3、TYPES-1、VIEW-1、WAYF-1、WEB>HTTP-1、WEB>HTTP-3、HTTP>WEB-1/2/3 等）**未发现问题，保留**。

| 规则 | AI 能照做吗 | 有检查证明吗 | 结论 |
| --- | --- | --- | --- |
| PAGE-3/4/5 用确认按钮、提交按钮、批量栏 | 能 | 没有强制（标 `part` 名不副实） | 改标 `reviewer` 或补 lint；PAGE-5 没有后端对应部分，等有了再保留 |
| PAGE-6 每个页面都被巡检 | — | 清单写死 | 改成对照表 + 完整性测试（发现 5） |
| PAGE-7 文字简短、按钮只写动词 | 模糊（"Sign in with a passkey"算不算？） | 仅审查，模版自己违规 | 改写成可判断的说法："以动词开头，最多 3 个词" |
| PAGE-8 和批准的截图一致 | — | 仅 Mac，本机 | 改对路径，如实写限制，基线进 git |
| PAGE-9 页面和布局只放布局类 | 能 | 仅审查，布局文件 7 行违规 | 改机器查（发现 8） |
| PAGE-10 前端不计数、不读时钟 | 部分模糊（搜索过滤、日历计算算不算？） | 仅审查 | 写明允许界面内搜索和日历网格计算；时钟部分改机器查 |
| COMP-3 每个组件开头说明用途 | 能 | 无；只有 shared 目录做到了 | 改成只要求 shared |
| COMP-4 共享组件都在 /style 展示 | 能 | 只查"被引入"，`import type` 也算 | 检查里排除 `import type` |
| COMP-5/6 带框字段和图标按钮用零件 | 能 | 没有强制 | 改机器查（发现 8） |
| COMP-7 只有 SubmitButton 提交 | 能 | lint 比文字窄 | 文字改成 lint 实际范围（发现 5） |
| COMP-10 有状态组件有同路径测试 | 能 | 仅审查；14 个模版文件不符 | 去掉"同路径"，改为"被某个测试真实引入" |
| LAYOUT-2 | — | — | 这条讲 /style，放错了卡片，挪到 comp.md |
| THEME-1/2 | — | — | 文字与事实不符（`cursor-pointer` 还出现在 2 个组件里；`--primary-pressed` 是写死的值），改文字 |
| WAYF-2 "每次都重新生成" | — | — | 不准确：只有 build 或 dev 时才生成，而静态检查在 build 之前跑。改文字，并在 `scripts/check.php` 里把 build 提到静态检查之前，或加一步 `wayfinder:generate` |
| WEB>HTTP-2/4 请求走 Inertia、不用 fetch | 能 | 仅审查 | 写明 `useHttp` 和 passkeys 包是允许的例外；改机器查 |
| HTTP>WEB-4 手写类型逐字段一致 | 能 | 仅审查；模版 4 处不符 | 规则里点名已有的 `tests/Feature/SharedDataTest.php:100-113`；修掉不符之处 |
| PAGE>COMP-2/3 不手写已有组件的标记 | 能 | 仅审查 | 改机器查 |

---

## 5. 最优方案

### 5.1 目标形态

**三根支柱不动**：骨架卡片（规则 + 保证人）、操作零件、工作流工具。流程仍是一条线：计划 → "开始" → 构建 → `composer check` → 审查 → 报告。不增加"小改动快速通道"，因为没法用机器可靠地判断什么算"小改动"，加了就是降低严格度。简化来自让每一步更便宜、更真。

| 部分 | 保留 | 删除 | 新增（解决什么问题） |
| --- | --- | --- | --- |
| 指令 | `AGENTS.md`、`CLAUDE.md`、`skeleton.md`、卡片 | 可选：10 张空卡片；过期文字 | 卡片里各一行"例子"（AI 不用每次自己找写法） |
| 工作流工具 | `scripts/workflow.php`、6 个 hook、计划格式 | — | 首次提交和"先提交再开始"的检查（门在新项目里不生效）；扩大的受保护文件清单（规则被悄悄改）；申诉通道（误报无出路） |
| 审查 | 分包机制、全局包 | `reviewer-haiku` 这个名字 | `reviewer`（sonnet）（漏判） |
| 机器检查 | PHPStan 分层规则、现有 oxlint 规则、结构测试 | — | 约 9 条新检查（把审查员负担减掉约五分之一，并且永不漏）；`failOnRisky`（空测试）；巡检完整性测试（新页面漏检）；副本一致性测试（规则走样） |
| 权限 | deny 两项 | — | 窄的 allow 清单（弹窗） |
| 代码 | 全部零件、`/style` | 死引用、`build:ssr` | — |
| 发布 | `template/`、CI、安装检查 | 本机旧 v2 标签（改名） | — |

### 5.2 典型任务推演：新增"联系我们"页面（带表单）

现在 AI 必须经过的步骤：

1. 会话开始，hook 告诉它"没有打开的计划"。
2. 自己查代码、数据库、卡片，写 `docs/exec-plans/active/contact.md`：目的、问题（附推荐答案）、决定（D1…）、屏幕（S1…）。
3. 一条消息问完所有问题；你回答；它把答案写进计划。
4. 你说"开始"，hook 记录批准。
5. 给每个决定和屏幕先写一个带名字的测试，再写代码（migration、Model、Factory、Policy、Action、路由、页面、组件、导航）。期间读写文件会自动加载约 28 张卡片（约 47 KB，加上常驻的 17 KB）。
6. 运行 `composer check`（格式、PHPStan、类型生成、oxlint、tsc、Vitest、构建、Pest 含 56 次以上浏览器访问），几分钟。
7. 运行 `php scripts/workflow.php review`，生成 5 个审查包，启动 5 个审查子代理。
8. 修掉每一条发现 → 再跑 `composer check` → 再审查还没干净的卡片，循环到全部 0 条。
9. 在计划里写"等你决定的事"，运行 `report`，用你的语言向你汇报。
10. 你合并；CI 再跑一次 `composer check`。

方案实施后的变化：

| 步骤 | 变化 | 严格度由什么保证 |
| --- | --- | --- |
| 1 | 新项目从第一天起 hook 就生效 | 安装脚本做首次提交；工具在无提交时拒绝写代码 |
| 5 | 卡片里有"例子"指路，少搜索、少走错路 | 规则不变 |
| 5–6 | 约 9 条规则在写代码时就被 lint 拦住，不用等审查 | 机器检查比 AI 审查更严，且每条有双向证明 |
| 6 | 新页面自动要求进巡检；空测试不再通过 | 完整性测试；`failOnRisky` |
| 6–8 | 流程命令不再逐条弹窗 | allow 清单逐条列出，不含可伪造的命令 |
| 7–8 | 审查规则变少，审查员更强，误报有正规出路，重审次数写进报告 | 审查员从 haiku 升到 sonnet，只会更严 |
| 9 | 报告后提交一次，下个计划的审查范围只含它自己 | "开始"时检查上个计划是否已提交 |

没有合并或删掉任何一个步骤。我检查过能否砍步骤：计划批准、按决定命名测试、完整检查、审查、报告各自管一件别的步骤管不了的事，砍掉任何一个都会降低严格度。

### 5.3 删除或合并清单

| 要删或改的 | 之后由什么保证不变松 |
| --- | --- |
| 约 9 条 `reviewer` 规则改成 `machine`（发现 8 的表） | 每条在 `template/tests/Lint/` 有"违规被拦 / 合规放行"两个样例，否则不改标 |
| `reviewer-haiku` → `reviewer`（sonnet） | 模型更强；`WorkflowTest` 断言随之更新 |
| 本机旧标签 `v2.0.0`–`v2.13.0`（改名） | 它们指向的提交仍在 `main` 的历史里 |
| 死引用、`build:ssr`、过期文字（发现 14） | `composer check` 仍通过；`CheckSettings::oxlint()` 钉住的列表同步更新 |
| 可选：10 张无规则的卡片 | 它们没有任何规则，删掉不影响审查范围（`workflow.php:1028` 本来就跳过无规则的卡片） |
| **不做**：合并卡片、把规则副本换成编号 | 已撤回，理由见发现 15 |

### 5.4 迁移步骤（按优先级）

每一步都单独成一个提交（或一个计划），这样可以单独退回。第 3 步起会改到受保护的检查文件和已有测试断言，按模版自己的规矩要写进计划、由你说"开始"。

| 顺序 | 改什么 | 怎么证明做对了 | 出问题怎么退 |
| --- | --- | --- | --- |
| 0 | 把项目文件夹复制一份到别处做备份 | 备份文件夹存在，文件数一致 | — |
| 1 | 在当前分支提交工作区全部改动（不含 `REVIEW-fable.md`） | `git status --short` 只剩本报告；`git show --stat HEAD` 约 620 个文件 | `git reset --soft HEAD~1` |
| 2 | 推送分支，开一个到 `main` 的 PR（不合并） | GitHub 上 CI 全绿，包括 `check-installer` 那一步 | 关闭 PR |
| 3 | 旧标签改名；CHANGELOG 旧小节改标题；改正 `README.md:60` | `git tag -l 'v2*'` 为空；`grep -c '^## \[2.0.0\]' template/CHANGELOG.md` → 1 | 把标签名改回去 |
| 4 | 首次提交和提交纪律（发现 2） | `template/tests` 新增用例：无提交的仓库里写代码被拒；`setup.php` 之后 `git log` 有 1 个提交；有未提交旧改动时"开始"被拦 | 退回该提交 |
| 5 | 补门（发现 5、6、7、10）：`failOnRisky` 并钉住；巡检对照表和完整性测试；基线进 git；`CHECK_FILES` 和 `isCodePath` 扩大；allow 清单；屏幕只认浏览器测试 | `composer check` 通过；故意新建一个页面文件不加进表 → 检查失败；故意加一个空测试 → 检查失败；改一张卡片 → 批准前被拒 | 每项单独提交，单独退回 |
| 6 | 修模版自己的违规（发现 8 的证据清单）、改正名不副实的标记和文字（4.20 的表）、修 `tech-debt.md` 的 4 条 | `composer check` 通过；截图基线只有计划里列出的页面变化 | 退回该提交 |
| 7 | 规则改机器查（发现 8 的表），一条一个提交，先做现有代码能直接通过的 | 每条：`template/tests/Lint/SkeletonChecksTest.php` 新增的两个方向样例通过；`npm run check` 和 `composer types:check` 在全仓库 0 报错；卡片上标记改为 `machine` | 退回那一条的提交，标记改回 `reviewer` |
| 8 | 审查员改名换模型、申诉通道、报告里的重审次数（发现 4） | `WorkflowTest` 更新后通过；在 `trial-160` 分支上拿一个真实计划试跑一轮，对比 haiku 和 sonnet 的结果 | 把 `model:` 改回 `haiku` |
| 9 | 升级流程文字（发现 9）、例子指路（发现 12）、副本一致性测试（发现 15）、新项目残留（发现 13）、死引用（发现 14） | `composer check` 通过；用 v1.3.0 建临时项目走一遍升级 | 退回对应提交 |
| 10 | 合并 PR，打 `v2.0.0` 标签并只推送这一个标签 | `git ls-remote --tags origin` 多出且只多出 `v2.0.0`；用 `laravel new` 建一个临时项目，`composer check` 通过，写代码前被要求先有计划 | 删除远端标签；Packagist 上该版本可撤 |

第 0–3 步今天就该做，合起来不到半小时。第 4–5 步是发布前必须的。第 6–9 步可以在发布 2.0.0 之后作为 2.1 做，但第 8 步我建议放在发布前。

---

## 6. 值得保留的做法

1. **每条规则标明保证人**（`machine` / `test` / `part` / `reviewer` / `note`）。这让"哪些有程序把关、哪些只靠判断"一目了然，本报告的大部分发现就是顺着这个标记查出来的。
2. **机器规则有双向证明**：`template/tests/Lint/SkeletonChecksTest.php` 用故意违规的样例证明检查会拦、用合规样例证明不会误拦（COMP-7、COMP-8、ACT>NOTE-1、CTRL-3 等）。本次运行 170 个测试全过。
3. **操作零件把顺序写死**：`app/Actions/OperationSteps.php:140-185`；同一个 `refusal()` 既决定按钮显不显示又决定操作能不能做（`app/Http/OperationGate.php:22-32`），按钮和实际结果不会不一致。`tests/Feature/Parts` 的 66 个测试是全仓库最扎实的，行锁测试用的是真的 InnoDB 锁。
4. **检查配置被测试钉住**：`tests/Structure/CheckSettings.php` 通过 `phpstan dump-parameters` 比对分层规则，AI 没法悄悄调低检查。全仓库搜索忽略注释和跳过测试，结果为零。
5. **批准门便宜而有效**：PreToolUse 在批准前拒绝写代码，计划内容用哈希锁定（`workflow.php:242-245`、`:670-705`）；每次 0.16 秒；Stop hook 只退回一次（`stop_hook_active`），不会死循环。
6. **Boost 的通用指南关闭、只留 MCP**：通用指南会和这套自定义写法冲突（例如它推荐 FormRequest 和 API Resource）。关掉是对的，而且 `boost:update` 不会覆盖 `CLAUDE.md`。
7. **测试用独立的 MySQL 库**，每个 worktree 一个（`tests/bootstrap.php:22-32`）；任何测试都碰不到真实外部服务（`tests/TestCase.php:15`）。
8. **模版自用的东西和项目分开**：`template/` 不进项目；CI 用真实的 Laravel Installer 建两个临时项目来验证安装（`.github/workflows/tests.yml:59-67`）。
9. **供应链和上线默认值**：GitHub Action 用提交哈希锁定、`permissions: contents: read`；`.npmrc` 的 `ignore-scripts=true`；生产环境禁止破坏性数据库命令并启用强密码规则（`app/Providers/AppServiceProvider.php:110-122`）；错误页不泄露异常信息（`bootstrap/app.php:54-63`）。
10. **前端类型从后端生成**，以及"旧代码的问题不顺手修、记账后集中清理"的纪律。

---

## 7. 需要你回答的问题

每题都有默认选择；你不回答，就按默认做。

1. **你用这个模版做什么类型的项目？**（任务说明里这一项还是 `[填写，例如：公司官网、SaaS 后台]`，没填。）
   默认：按"SaaS 后台 + 公司官网"两种都做来处理，公开页面组件和后台组件都保留。
2. **项目的界面只要英文，还是需要中文或多语言？** 这一版把双语整个删了。
   默认：保持单语言英文。提醒：如果你知道多数项目要中文界面，请现在说，发布前恢复比发布后再加便宜得多。
3. **有没有已经上线、用 1.x 做的项目需要升到 2.0？** 2.0 把主键改成了 UUID，并直接改了已有的数据库迁移文件，已有数据的项目不能直接套用。
   默认：没有，不做 1.x → 2.0 的数据迁移支持。
4. **只在演示页用到的组件（看板、月历、图表、定价表、向导、结账汇总等）和为它们装的 `recharts`、`@dnd-kit`，保留还是移出？**
   默认：这一轮不动。用模版做完头两个真实项目后，把一次都没用到的删掉。
5. **审查员换成更强的模型，每次审查会更贵，可以接受吗？**
   默认：可以，换成 sonnet。依据是你自己的记录里 haiku 连续漏掉严重问题。
6. **要不要我现在补跑会写文件的检查**（`npm run build`、浏览器测试、类型生成检查）？写入位置见 2.2。
   默认：不跑，改由第 5.4 节第 2 步的 CI 来验证。回复"继续"我就跑。

---

## 8. 覆盖范围说明

**我完整读过**：`AGENTS.md`、`CLAUDE.md`、`.claude/rules/skeleton.md`、全部 49 张卡片、`.claude/settings.json`、两个子代理定义、`wayfinder-development/SKILL.md`、`.mcp.json`、`boost.json`、`docs/workflow.md`、`docs/PLANS.md`、`docs/exec-plans/tech-debt.md`、`README.md`、`CONTRIBUTING.md`、`scripts/workflow.php`（1812 行）、`scripts/check.php`、`check-process.php`、`check-generated-types.php`、`setup.php`、`composer.json`、`package.json`、两个 `phpunit.xml`、`phpstan.neon`、`vite.config.ts`、`.github/` 下两个文件、`.env.example`、`.gitignore`、`.gitattributes`、`copier.yml`、`app/Actions/` 下的 `Operation`、`OperationSteps`、`UserlessOperation`、`After`、`OutsideCall`、`OperationController`、`WebhookController`、`DownloadController`、`OperationJob`、`OperationGate`、`UrlRecords`、三个 Provider、`bootstrap/app.php`、`routes/` 全部、`tests/bootstrap.php`、`tests/Pest.php`、`tests/TestCase.php`、`tests/Browser/PagesTest.php`。

**由子代理通读、我抽查核对**：`tests/**`（188 个文件）、`template/tests/**`、`tests/Structure/*`、`resources/js/**`（212 个手写文件）、`lint/oxlint/ezdev.js`、`resources/css/app.css`。Claude Code 的 18 项行为由子代理逐项对照官方文档（code.claude.com/docs 的 hooks、permissions、memory、sub-agents、mcp、env-vars 页面）。

**只抽查**：`template/CHANGELOG.md`（前 60 行和全部标题）、`template/plans/template-simplification.md`（第 164–176 行）、`template/tests` 的个别段落、`template/scripts/check-installer`（只搜索关键词）、`tsconfig.json`（非注释行）、Laravel Installer 和 Boost 的源码（只搜索相关行）、`app/` 其余文件（`ListingController`、`StoredFile` 只看了函数签名和关键行；`DeletePasskey` 看了片段；Models、Policies、Resources、Middleware、Requests、其余 Actions 没有逐行读）、`config/app.php` 和 `config/session.php`（只看了几行关键设置）。

**完全没看**：`config/` 其余文件、`database/` 下的迁移、工厂、填充文件的内容、`resources/views/app.blade.php`、`public/`、`storage/`、`scripts/worktree-up`、`lint/phpstan/DisallowedTypeResolver.php`、`template/plans/` 下其余 19 个计划、`LICENSE` 和 `SECURITY.md` 的正文、另外 7 个 git worktree、`main` 到 HEAD 之间 123 个提交各自的内容。

**没有实际验证的事**：2.0 的安装流程（没有新建项目）；升级流程（没装 `copier`）；浏览器测试和构建（等你回复"继续"）；无断言测试的实际退出码；Composer 选版本的行为；sonnet 作为审查员的准确率。每一项的验证办法写在对应的发现里。

### 第 4 步复核结果

一个没有参与审查的子代理拿到 19 条结论和 6 步方案，被要求逐条找原文反驳。结果：

- **15 条确认，4 条部分成立并已按它的意见改正**：卡片是 49 张不是 51 张；空测试并非完全无人把关（TEST-3 审查规则点名了"无断言"）；密码重置的底层动作有真实测试，只是 HTTP 一层没有；误报虽无申诉通道，但可以无限重跑审查。
- **它推翻了我方案里的两处**：① "推送分支让 CI 跑"不成立，CI 只在 `main` 和 PR 上触发，已改成开 PR；② "合并卡片、副本换成编号"会让部分路径悄悄失去审查，已撤回。
- **它补充了我漏掉的问题**，我逐一核对原文后采纳：新项目首次提交前 hook 不生效（发现 2）；升级流程与 hook 冲突（发现 9）；"检查通过"和"审查通过"可伪造、项目里工具无测试（发现 7）；模版自己违反 ACT-14（发现 8）；haiku 对比 opus 的历史记录（发现 4）。
- 它对方案第 3 步的具体提醒（ROUTE-2 不能直接改标、PAGE-9 用允许清单容易误报、MIGR-1 要豁免模版同步计划）已写进发现 8。

---

## 附录 A：开始和结束时的 git 状态

**开始时**：`git rev-parse HEAD` → `b849d16184ea502214250eff64cd52386f7b2b9b`；`git status` 列出 512 项。下面是同一状态的 `git status --porcelain` 快照（在运行第 2 步的任何命令之前保存；`M` = 修改，`D` = 删除，`??` = 未跟踪）。

**结束时**（写完本报告之后）：`git rev-parse HEAD` → `b849d16184ea502214250eff64cd52386f7b2b9b`；`git status --porcelain` 共 513 项。和开始的快照逐行对比（`diff`），唯一的差异是：

```
430a431
> ?? REVIEW-fable.md
```

也就是说，除了本报告文件，仓库里没有任何变化。

上面的对比只能看出文件有没有增减，看不出已修改文件的内容是否又变了，所以另做了两项内容级的核对：`find . -type f -newermt '2026-11-22 19:34:00'`（排除 vendor、node_modules、.git 和被忽略的文件）只列出 `./REVIEW-fable.md`；`php scripts/workflow.php fingerprint` 的结果 `8609fde196aa5c67175e9095860502d3cdc0992c` 和 19:35:09 记录在 `.git/ezdev/passed` 里的值相同。

<details>
<summary>开始时的完整快照（512 行，点击展开）</summary>

```
 M .claude/agents/reviewer-haiku.md
 D .claude/agents/reviewer-opus.md
 D .claude/rules/actions.md
 D .claude/rules/app.md
 D .claude/rules/checks.md
 D .claude/rules/controllers.md
 D .claude/rules/fixes.md
 D .claude/rules/form-requests.md
 D .claude/rules/frontend.md
 D .claude/rules/models.md
 D .claude/rules/tests.md
 D .claude/rules/translations.md
 D .claude/rules/visual-design.md
 M .claude/settings.json
 M .env.example
 M .gitattributes
 M .github/workflows/tests.yml
 M .gitignore
 M .mcp.json
 M AGENTS.md
 D ARCHITECTURE.md
 M CONTRIBUTING.md
 D DESIGN.md
 M README.md
 D README.zh-CN.md
 M app/Actions/Notifications/MarkAllNotificationsRead.php
 M app/Actions/Notifications/MarkNotificationRead.php
 D app/Actions/Users/CreateUser.php
 M app/Actions/Users/DeleteUser.php
 D app/Actions/Users/UpdateLocale.php
 D app/Actions/Users/UpdatePassword.php
 M app/Actions/Users/UpdateProfile.php
 M app/Concerns/ProfileValidationRules.php
 D app/Console/Commands/InitializeProject.php
 M app/Enums/Appearance.php
 D app/Enums/Locale.php
 M app/Enums/PerPage.php
 D app/Http/Controllers/AllNotificationsReadController.php
 D app/Http/Controllers/LocaleController.php
 D app/Http/Controllers/NotificationController.php
 D app/Http/Controllers/NotificationReadController.php
 M app/Http/Controllers/Settings/ProfileController.php
 M app/Http/Controllers/Settings/SecurityController.php
 M app/Http/Fortify/CreateNewUser.php
 M app/Http/Fortify/ResetUserPassword.php
 M app/Http/Middleware/HandleInertiaRequests.php
 D app/Http/Middleware/SetLocale.php
 D app/Http/Requests/Locale/UpdateLocaleRequest.php
 D app/Http/Requests/Notifications/IndexNotificationRequest.php
 D app/Http/Requests/Settings/PasswordUpdateRequest.php
 D app/Http/Requests/Settings/ProfileDeleteRequest.php
 D app/Http/Requests/Settings/ProfileUpdateRequest.php
 M app/Http/Resources/NotificationResource.php
 M app/Http/Resources/PasskeyResource.php
 M app/Http/Resources/UserResource.php
 D app/Integrations/.gitkeep
 M app/Models/Notification.php
 M app/Models/User.php
 M app/Notifications/InAppNotification.php
 M app/Policies/NotificationPolicy.php
 M app/Policies/UserPolicy.php
 M app/Providers/AppServiceProvider.php
 M app/Providers/FortifyServiceProvider.php
 D app/Support/InstallBoost.php
 M bootstrap/app.php
 M composer.json
 M composer.lock
 M config/filesystems.php
 D config/laravel-pdf.php
 D config/starter-kit.php
 M copier.yml
 M database/factories/PasskeyFactory.php
 M database/migrations/0001_01_01_000000_create_users_table.php
 M database/migrations/2024_01_01_000000_create_passkeys_table.php
 M database/migrations/2026_11_01_000000_create_notifications_table.php
 D database/migrations/2026_11_14_000000_add_locale_to_users_table.php
 M docs/PLANS.md
 D docs/design/components.md
 D docs/design/foundations.md
 D docs/design/patterns/approval.md
 D docs/design/patterns/auth.md
 D docs/design/patterns/bulk-actions.md
 D docs/design/patterns/calendar.md
 D docs/design/patterns/checkout.md
 D docs/design/patterns/contact.md
 D docs/design/patterns/customer-area.md
 D docs/design/patterns/dashboard.md
 D docs/design/patterns/detail.md
 D docs/design/patterns/error-page.md
 D docs/design/patterns/feed.md
 D docs/design/patterns/form.md
 D docs/design/patterns/help.md
 D docs/design/patterns/import-export.md
 D docs/design/patterns/kanban.md
 D docs/design/patterns/landing.md
 D docs/design/patterns/list-detail.md
 D docs/design/patterns/list.md
 D docs/design/patterns/pricing.md
 D docs/design/patterns/print.md
 D docs/design/patterns/product.md
 D docs/design/patterns/report.md
 D docs/design/patterns/search-results.md
 D docs/design/patterns/settings.md
 D docs/design/patterns/states.md
 D docs/design/patterns/wizard.md
 D docs/exec-plans/active/template-1.6.0.md
 M docs/exec-plans/tech-debt.md
 D docs/golden-principles.md
 D docs/prompts/cleanup.md
 D docs/prompts/review.md
 D docs/prompts/upgrade.md
 D docs/reference/pages/auth/login.tsx
 D docs/reference/pages/auth/register.tsx
 D docs/reference/pages/components/notifications/notification-row.tsx
 D docs/reference/pages/dashboard/index.tsx
 D docs/reference/pages/errors/show.tsx
 D docs/reference/pages/notifications/index.tsx
 D docs/reference/pages/settings/appearance.tsx
 D docs/reference/pages/settings/profile.tsx
 D docs/reference/pages/settings/security.tsx
 D docs/reference/pages/welcome/index.tsx
 D docs/reference/tasks/README.md
 D docs/reference/tasks/app/Actions/Tasks/CompleteOverdueTasks.php
 D docs/reference/tasks/app/Actions/Tasks/CompleteTask.php
 D docs/reference/tasks/app/Actions/Tasks/CompleteTasks.php
 D docs/reference/tasks/app/Actions/Tasks/CreateTask.php
 D docs/reference/tasks/app/Actions/Tasks/DeleteTask.php
 D docs/reference/tasks/app/Actions/Tasks/DeleteTasks.php
 D docs/reference/tasks/app/Actions/Tasks/ImportTasks.php
 D docs/reference/tasks/app/Actions/Tasks/UpdateTask.php
 D docs/reference/tasks/app/Concerns/ReadsTaskFilters.php
 D docs/reference/tasks/app/Concerns/TaskValidationRules.php
 D docs/reference/tasks/app/Enums/TaskSort.php
 D docs/reference/tasks/app/Enums/TaskStatus.php
 D docs/reference/tasks/app/Http/Controllers/BulkTaskCompletionController.php
 D docs/reference/tasks/app/Http/Controllers/BulkTaskDeletionController.php
 D docs/reference/tasks/app/Http/Controllers/OverdueTaskCompletionController.php
 D docs/reference/tasks/app/Http/Controllers/TaskCompletionController.php
 D docs/reference/tasks/app/Http/Controllers/TaskController.php
 D docs/reference/tasks/app/Http/Controllers/TaskExportController.php
 D docs/reference/tasks/app/Http/Controllers/TaskImportController.php
 D docs/reference/tasks/app/Http/Controllers/TaskImportTemplateController.php
 D docs/reference/tasks/app/Http/Controllers/TaskPdfController.php
 D docs/reference/tasks/app/Http/Controllers/TaskPrintController.php
 D docs/reference/tasks/app/Http/Requests/Tasks/BulkTaskRequest.php
 D docs/reference/tasks/app/Http/Requests/Tasks/ImportTaskRequest.php
 D docs/reference/tasks/app/Http/Requests/Tasks/IndexTaskRequest.php
 D docs/reference/tasks/app/Http/Requests/Tasks/StoreTaskRequest.php
 D docs/reference/tasks/app/Http/Requests/Tasks/UpdateTaskRequest.php
 D docs/reference/tasks/app/Http/Resources/TaskResource.php
 D docs/reference/tasks/app/Models/Task.php
 D docs/reference/tasks/app/Policies/TaskPolicy.php
 D docs/reference/tasks/app/Support/TaskCsv.php
 D docs/reference/tasks/database/factories/TaskFactory.php
 D docs/reference/tasks/database/migrations/2026_10_24_000000_create_tasks_table.php
 D docs/reference/tasks/resources/js/components/tasks/task-actions.tsx
 D docs/reference/tasks/resources/js/components/tasks/task-bulk-actions.tsx
 D docs/reference/tasks/resources/js/components/tasks/task-fields.tsx
 D docs/reference/tasks/resources/js/components/tasks/task-form-dialog.tsx
 D docs/reference/tasks/resources/js/components/tasks/task-import-dialog.tsx
 D docs/reference/tasks/resources/js/components/tasks/task-table.tsx
 D docs/reference/tasks/resources/js/pages/tasks/index.tsx
 D docs/reference/tasks/resources/js/pages/tasks/print.tsx
 D docs/reference/tasks/resources/js/pages/tasks/show.tsx
 D docs/reference/tasks/resources/js/types/task.ts
 D docs/reference/tasks/resources/views/tasks/pdf.blade.php
 D docs/reference/tasks/routes/tasks.php
 D docs/reference/tasks/tests/Feature/Tasks/CompleteOverdueTasksTest.php
 D docs/reference/tasks/tests/Feature/Tasks/CompleteTaskTest.php
 D docs/reference/tasks/tests/Feature/Tasks/CompleteTasksTest.php
 D docs/reference/tasks/tests/Feature/Tasks/CreateTaskTest.php
 D docs/reference/tasks/tests/Feature/Tasks/DeleteTaskTest.php
 D docs/reference/tasks/tests/Feature/Tasks/DeleteTasksTest.php
 D docs/reference/tasks/tests/Feature/Tasks/ImportTasksTest.php
 D docs/reference/tasks/tests/Feature/Tasks/TaskBulkActionsTest.php
 D docs/reference/tasks/tests/Feature/Tasks/TaskButtonsTest.php
 D docs/reference/tasks/tests/Feature/Tasks/TaskControllerTest.php
 D docs/reference/tasks/tests/Feature/Tasks/TaskExportTest.php
 D docs/reference/tasks/tests/Feature/Tasks/TaskImportTest.php
 D docs/reference/tasks/tests/Feature/Tasks/TaskListQueryTest.php
 D docs/reference/tasks/tests/Feature/Tasks/TaskPrintTest.php
 D docs/reference/tasks/tests/Feature/Tasks/TaskRulesTest.php
 D docs/reference/tasks/tests/Feature/Tasks/UpdateTaskTest.php
 M docs/workflow.md
 D lang/en/auth.php
 D lang/en/pagination.php
 D lang/en/passwords.php
 D lang/en/validation.php
 D lang/zh_CN.json
 D lang/zh_CN/auth.php
 D lang/zh_CN/pagination.php
 D lang/zh_CN/passwords.php
 D lang/zh_CN/validation.php
 M lint/oxlint/ezdev.js
 M package-lock.json
 M package.json
 M phpstan.neon
 M phpunit.xml
 M resources/css/app.css
 D resources/fonts/NotoSansSC-Bold.ttf
 D resources/fonts/NotoSansSC-Regular.ttf
 D resources/fonts/OFL.txt
 M resources/js/app.tsx
 M resources/js/components/app-sidebar.tsx
 M resources/js/components/breadcrumbs.tsx
 M resources/js/components/heading.tsx
 M resources/js/components/nav-main.tsx
 M resources/js/components/notifications/latest-notifications.tsx
 M resources/js/components/notifications/mark-all-read-button.tsx
 M resources/js/components/notifications/notification-menu.tsx
 M resources/js/components/notifications/notification-row.tsx
 D resources/js/components/password-input.tsx
 M resources/js/components/shared/account-nav.tsx
 M resources/js/components/shared/actions.tsx
 M resources/js/components/shared/bulk-action-bar.tsx
 M resources/js/components/shared/column-menu.tsx
 M resources/js/components/shared/confirm-dialog.tsx
 M resources/js/components/shared/contact-form.tsx
 M resources/js/components/shared/date-picker.tsx
 M resources/js/components/shared/error-content.tsx
 M resources/js/components/shared/error-state.tsx
 M resources/js/components/shared/faq-list.tsx
 M resources/js/components/shared/feature-grid.tsx
 M resources/js/components/shared/feed-list.tsx
 M resources/js/components/shared/file-picker.tsx
 M resources/js/components/shared/form-field.tsx
 M resources/js/components/shared/hero.tsx
 M resources/js/components/shared/kanban-board.tsx
 D resources/js/components/shared/language-switch.tsx
 M resources/js/components/shared/line-items.tsx
 M resources/js/components/shared/list-toolbar.tsx
 M resources/js/components/shared/loading-state.tsx
 M resources/js/components/shared/month-calendar.tsx
 M resources/js/components/shared/order-summary.tsx
 M resources/js/components/shared/pagination.tsx
 M resources/js/components/shared/pricing-plans.tsx
 M resources/js/components/shared/public-footer.tsx
 M resources/js/components/shared/public-header.tsx
 M resources/js/components/shared/quick-links.tsx
 M resources/js/components/shared/report-chart.tsx
 M resources/js/components/shared/report-range.tsx
 M resources/js/components/shared/responsive-dialog.tsx
 M resources/js/components/shared/responsive-table.tsx
 M resources/js/components/shared/row-actions.tsx
 M resources/js/components/shared/search-box.tsx
 M resources/js/components/shared/search-results.tsx
 M resources/js/components/shared/sort-filter-sheet.tsx
 M resources/js/components/shared/stat-tiles.tsx
 M resources/js/components/shared/url-search-input.tsx
 M resources/js/components/shared/wizard-layout.tsx
 M resources/js/components/style/approval-demo.tsx
 M resources/js/components/style/auth-demo.tsx
 M resources/js/components/style/bulk-actions-demo.tsx
 M resources/js/components/style/calendar-demo.tsx
 M resources/js/components/style/checkout-demo.tsx
 M resources/js/components/style/contact-demo.tsx
 M resources/js/components/style/customer-area-demo.tsx
 M resources/js/components/style/customer-examples.ts
 M resources/js/components/style/dashboard-demo.tsx
 M resources/js/components/style/detail-demo.tsx
 M resources/js/components/style/detail-tabs-demo.tsx
 M resources/js/components/style/feed-demo.tsx
 M resources/js/components/style/form-demo.tsx
 M resources/js/components/style/help-demo.tsx
 M resources/js/components/style/import-export-demo.tsx
 M resources/js/components/style/kanban-demo.tsx
 M resources/js/components/style/landing-demo.tsx
 M resources/js/components/style/list-demo.tsx
 M resources/js/components/style/list-detail-demo.tsx
 M resources/js/components/style/pricing-demo.tsx
 M resources/js/components/style/print-demo.tsx
 M resources/js/components/style/product-demo.tsx
 M resources/js/components/style/public-frame.tsx
 M resources/js/components/style/report-demo.tsx
 M resources/js/components/style/row-actions-demo.tsx
 M resources/js/components/style/search-demo.tsx
 M resources/js/components/style/settings-demo.tsx
 M resources/js/components/style/shared-demo.tsx
 M resources/js/components/style/states-demo.tsx
 M resources/js/components/style/style-section.tsx
 M resources/js/components/style/tokens-demo.tsx
 M resources/js/components/style/ui-actions-demo.tsx
 M resources/js/components/style/ui-fields-demo.tsx
 M resources/js/components/style/ui-surfaces-demo.tsx
 M resources/js/components/style/wizard-demo.tsx
 M resources/js/components/ui/badge.tsx
 M resources/js/components/ui/breadcrumb.tsx
 M resources/js/components/ui/button.tsx
 M resources/js/components/ui/calendar.tsx
 M resources/js/components/ui/chart.tsx
 M resources/js/components/ui/dialog.tsx
 M resources/js/components/ui/sheet.tsx
 M resources/js/components/ui/sidebar.tsx
 M resources/js/components/ui/sonner.tsx
 M resources/js/components/ui/spinner.tsx
 M resources/js/components/ui/table.tsx
 M resources/js/components/user-info.tsx
 M resources/js/components/user-menu-content.tsx
 M resources/js/components/users/appearance-tabs.tsx
 M resources/js/components/users/delete-user.tsx
 D resources/js/components/users/language-toggle.tsx
 M resources/js/components/users/manage-passkeys.tsx
 M resources/js/components/users/manage-two-factor.tsx
 M resources/js/components/users/new-password-fields.tsx
 M resources/js/components/users/passkey-item.tsx
 M resources/js/components/users/passkey-register.tsx
 M resources/js/components/users/passkey-verify.tsx
 M resources/js/components/users/two-factor-code-input.tsx
 M resources/js/components/users/two-factor-recovery-codes.tsx
 M resources/js/components/users/two-factor-setup-modal.tsx
 M resources/js/components/users/two-factor-setup-step.tsx
 M resources/js/components/users/two-factor-verification-step.tsx
 M resources/js/hooks/use-clipboard.ts
 D resources/js/hooks/use-language.ts
 M resources/js/hooks/use-network-error-toast.ts
 M resources/js/hooks/use-two-factor-auth.ts
 M resources/js/hooks/use-validation-error-toasts.ts
 M resources/js/layouts/app/app-sidebar-layout.tsx
 M resources/js/layouts/auth/auth-simple-layout.tsx
 D resources/js/layouts/locale-layout.tsx
 M resources/js/layouts/public-layout.tsx
 M resources/js/layouts/settings/layout.tsx
 M resources/js/lib/format.ts
 D resources/js/lib/i18n.ts
 M resources/js/lib/kanban.ts
 M resources/js/lib/month-grid.ts
 M resources/js/pages/auth/confirm-password.tsx
 M resources/js/pages/auth/forgot-password.tsx
 M resources/js/pages/auth/login.tsx
 M resources/js/pages/auth/register.tsx
 M resources/js/pages/auth/reset-password.tsx
 M resources/js/pages/auth/two-factor-challenge.tsx
 M resources/js/pages/auth/verify-email.tsx
 M resources/js/pages/dashboard/index.tsx
 M resources/js/pages/errors/show.tsx
 M resources/js/pages/notifications/index.tsx
 M resources/js/pages/settings/appearance.tsx
 M resources/js/pages/settings/profile.tsx
 M resources/js/pages/settings/security.tsx
 M resources/js/pages/style/index.tsx
 M resources/js/pages/welcome/index.tsx
 M resources/js/types/auth.ts
 M resources/js/types/global.d.ts
 D resources/js/types/notification.ts
 M routes/console.php
 D routes/locale.php
 M routes/notifications.php
 M routes/settings.php
 M routes/web.php
 D scripts/check-milestone.php
 M scripts/check-process.php
 D scripts/check-reference
 M scripts/check.php
 D scripts/hooks/finish-check.php
 D scripts/playwright-mcp
 D scripts/screen-round
 D scripts/screen-round.php
 M template/CHANGELOG.md
 M template/scripts/check-installer
 D template/tests/Hooks/FinishCheckTest.php
 M template/tests/Lint/FixturesTest.php
 D template/tests/Lint/SampleCopiesTest.php
 M template/tests/Lint/SkeletonChecksTest.php
 M tests/Feature/Auth/AuthenticationTest.php
 M tests/Feature/ErrorPageTest.php
 D tests/Feature/Installation/InitializeProjectTest.php
 D tests/Feature/Locale/LocaleChoicesTest.php
 D tests/Feature/Locale/LocaleControllerTest.php
 D tests/Feature/Locale/LocaleSentencesTest.php
 D tests/Feature/Locale/SetLocaleTest.php
 D tests/Feature/Locale/TranslatedTextTest.php
 M tests/Feature/Notifications/MarkAllNotificationsReadTest.php
 M tests/Feature/Notifications/MarkNotificationReadTest.php
 M tests/Feature/Notifications/NotificationButtonsTest.php
 M tests/Feature/Notifications/NotificationControllerTest.php
 M tests/Feature/Settings/PasskeyManagementTest.php
 M tests/Feature/Settings/ProfileUpdateTest.php
 M tests/Feature/Settings/SecurityTest.php
 M tests/Feature/SharedDataTest.php
 M tests/Feature/TestsRowLocksTest.php
 D tests/Feature/Users/CreateUserTest.php
 M tests/Feature/Users/DeleteUserTest.php
 D tests/Feature/Users/UpdateLocaleTest.php
 D tests/Feature/Users/UpdatePasswordTest.php
 M tests/Feature/Users/UpdateProfileTest.php
 M tests/Feature/Users/UserPolicyTest.php
 M tests/Pest.php
 M tests/Structure/CheckSettings.php
 M tests/Structure/CodeTest.php
 M tests/Structure/ConfigTest.php
 D tests/Structure/DesignDocChecks.php
 D tests/Structure/DesignDocsTest.php
 M tests/Structure/FoldersTest.php
 M tests/Structure/PlacementChecks.php
 M tests/Support/TestsRowLocks.php
 D tests/Support/Workspace.php
 M tests/TestCase.php
 D tests/Unit/Enums/LocaleTest.php
 D tests/Unit/Hooks/MilestoneCheckTest.php
 D tests/Unit/Hooks/PlaywrightMcpTest.php
 D tests/Unit/Hooks/ReviewRoundTest.php
 D tests/Unit/Hooks/ScreenRoundTest.php
 M tests/js/components/notifications/latest-notifications.test.tsx
 M tests/js/components/notifications/notification-row.test.tsx
 D tests/js/components/shared/calendar-language.test.tsx
 M tests/js/components/shared/column-menu.test.tsx
 M tests/js/components/shared/contact-form.test.tsx
 M tests/js/components/shared/date-picker.test.tsx
 M tests/js/components/shared/form-field.test.tsx
 M tests/js/components/shared/kanban-board.test.tsx
 D tests/js/components/shared/language-switch.test.tsx
 M tests/js/components/shared/list-detail-layout.test.tsx
 M tests/js/components/shared/month-calendar.test.tsx
 M tests/js/components/shared/pagination.test.tsx
 M tests/js/components/shared/public-header.test.tsx
 M tests/js/components/shared/public-layout.test.tsx
 M tests/js/components/shared/responsive-table.test.tsx
 M tests/js/components/style/action-rows.test.tsx
 M tests/js/components/style/customer-demos.test.tsx
 M tests/js/components/style/form-demo.test.tsx
 M tests/js/components/style/row-actions-demo.test.tsx
 D tests/js/layouts/locale-layout.test.tsx
 M tests/js/lib/format.test.ts
 D tests/js/lib/i18n.test.ts
 M tests/js/lib/month-grid.test.ts
 M tests/js/pages/welcome.test.tsx
 M tests/js/press-states.test.ts
 M vite.config.ts
?? .claude/rules/skeleton.md
?? .claude/rules/skeleton/
?? app/Actions/After.php
?? app/Actions/Operation.php
?? app/Actions/OperationSteps.php
?? app/Actions/OutsideCall.php
?? app/Actions/UserlessOperation.php
?? app/Actions/Users/ChangePassword.php
?? app/Actions/Users/DeletePasskey.php
?? app/Actions/Users/RegisterUser.php
?? app/Actions/Users/ResetPassword.php
?? app/Enums/Concerns/
?? app/Http/Controllers/DownloadController.php
?? app/Http/Controllers/ListingController.php
?? app/Http/Controllers/OperationController.php
?? app/Http/Controllers/RecordController.php
?? app/Http/Controllers/WebhookController.php
?? app/Http/OperationGate.php
?? app/Http/Requests/ListingRequest.php
?? app/Http/Resources/ExportsCsv.php
?? app/Http/Resources/NotificationCan.php
?? app/Http/Resources/NotificationSummary.php
?? app/Http/Resources/NotificationSummaryCan.php
?? app/Http/Resources/ResourceData.php
?? app/Http/Resources/ResourceShape.php
?? app/Http/Resources/SharedAuth.php
?? app/Http/Resources/Today.php
?? app/Http/UrlRecords.php
?? app/Jobs/
?? app/Models/Passkey.php
?? app/Policies/PasskeyPolicy.php
?? app/Providers/TypeScriptTransformerServiceProvider.php
?? app/Support/StoredFile.php
?? resources/js/components/shared/checkbox-field.tsx
?? resources/js/components/shared/closing-call.tsx
?? resources/js/components/shared/confirm-button.tsx
?? resources/js/components/shared/dashboard-grid.tsx
?? resources/js/components/shared/detail-list.tsx
?? resources/js/components/shared/export-button.tsx
?? resources/js/components/shared/icon-button.tsx
?? resources/js/components/shared/page-header.tsx
?? resources/js/components/shared/password-field.tsx
?? resources/js/components/shared/section.tsx
?? resources/js/components/shared/select-field.tsx
?? resources/js/components/shared/stacked-form.tsx
?? resources/js/components/shared/status-message.tsx
?? resources/js/components/shared/submit-button.tsx
?? resources/js/components/shared/text-field.tsx
?? resources/js/components/shared/textarea-field.tsx
?? resources/js/components/users/auth-prompt.tsx
?? resources/js/components/users/email-verification-notice.tsx
?? resources/js/layouts/error-layout.tsx
?? resources/js/types/generated.d.ts
?? scripts/check-generated-types.php
?? scripts/setup.php
?? scripts/workflow.php
?? template/plans/template-1.6.0.md
?? template/tests/Hooks/CheckRunTest.php
?? template/tests/Hooks/WorkflowTest.php
?? template/tests/Installation/
?? template/tests/Support/
?? tests/.pest/
?? tests/Browser/
?? tests/Feature/DevCommandTest.php
?? tests/Feature/Notifications/NotificationMessageTest.php
?? tests/Feature/PartRoutesTest.php
?? tests/Feature/Parts/
?? tests/Feature/Users/PasswordTest.php
?? tests/Feature/Users/RegisterUserTest.php
?? tests/Unit/Enums/ReadsChoiceTest.php
?? tests/js/components/notifications/notification-menu.test.tsx
?? tests/js/components/shared/confirm-button.test.tsx
?? tests/js/components/shared/error-content.test.tsx
?? tests/js/components/shared/export-button.test.tsx
?? tests/js/components/shared/password-field.test.tsx
?? tests/js/components/shared/sort-filter-sheet.test.tsx
?? tests/js/components/shared/submit-button.test.tsx
?? tests/js/components/style/other-demos.test.tsx
?? tests/js/components/users/passkeys.test.tsx
?? tests/js/components/users/two-factor.test.tsx
?? tests/js/hooks/use-clipboard.test.tsx
?? tests/js/hooks/use-flash-toast.test.tsx
?? tests/js/hooks/use-two-factor-auth.test.tsx
?? tests/js/hooks/use-url-param.test.tsx
```

</details>
