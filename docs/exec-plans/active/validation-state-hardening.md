# 检查结果有效性与最终验收修复

## Purpose
修复检查结果被错误复用的三个缺口，并将 elelong-auction 已验证的无效参数重试修复合入母板。保持每个开发 milestone 执行全量静态检查和受影响测试、整份计划完成时执行完整检查的工作方式。

## Agreed decisions
1. 用户已确定：每个 milestone 检查受影响行为及其调用方，整份计划完成后必须执行完整检查。局部通过不能冒充全量通过。
2. 用户此前已授权母板修改、提交、推送及 Packagist 发布；2026-11-10 用户回复“开始”，授权按本计划实施。
3. 本次审计确认原来的六种无效参数重试已在 elelong-auction 修好，又复现了跨会话结束计划、重叠运行串用记录、全量失败后复用局部通过记录三个缺口。
4. 按以下里程碑实施，开发阶段只执行受影响测试，完成实现后执行完整验收与发布。

## Milestones
- [x] M1 失败重试：先补回归测试，合入无效参数重试修复，统一撤销失败或未完成检查所对应的旧通过记录。
- [x] M2 并发隔离：先补真实子进程回归，再让全量和局部检查使用同一工作树锁及独立运行身份，防止串用开始记录和完成凭据。
- [ ] M3 跨会话验收：先补连续会话回归，再使计划完成和归档必须检查当前代码的全量通过凭据，不依赖本会话是否修改代码。
- [ ] M4 最终验收与发布：完整检查母板及 reference，独立审查，验证 auction 兼容性，提交推送并发布补丁版本，核对 CI 与 Packagist。

## Validation
每个开发里程碑先将对应缺口写成失败测试，再修实现；已有业务断言保留。使用临时 Git 仓库和真实 runner/hook 子进程验证退出码、实际通过记录和 Stop 输出。耗时的业务检查可在这些编排单测中使用可控替身，完整验收仍运行真实套件。

M1 的修改范围是 `scripts/check-milestone.php`、`scripts/hooks/finish-check.php` 和 `tests/Unit/Hooks/`：
- 合入 auction 的六种无效重试场景：无参数、文件不存在、非测试文件、空目录、runner 选项、父目录穿越。
- 覆盖已有局部通过后全量失败、已有全量通过后全量失败，以及局部失败不能借旧全量记录放行。
- 检查失败、异常和开始后没有成功结束时均不能形成新通过记录；一次后续真实成功可以恢复通过状态。
- 将失效规则集中在一个状态管理入口，避免全量与局部 runner 分别实现。

M2 的修改范围是上述脚本、全量命令编排及其结构测试：
- 全量和局部命令在同一工作树中共用互斥锁；第二个重叠命令快速返回明确的忙碌结果，不覆盖正在运行的记录。不同 worktree 保持隔离。
- 每次执行携带独立运行身份，开始和结束必须属于同一次执行；旧的、缺失的或被另一轮替换的身份不能写入通过记录。
- 锁覆盖整个子进程检查周期，正常结束和异常退出释放；进程被终止后不能遗留永久占用的锁，也不能把未结束检查视为通过。
- 覆盖局部/局部、全量/全量、全量/局部重叠；用同步信号确定执行顺序，不靠固定延时碰运气。
- 保留审计的关键场景：A 检查旧代码后暂停，代码改成 PHP 语法错误，B 发起无效重试，A 结束；新代码必须不能得到通过记录。
- 必要时新增 `scripts/check.php` 作为全量命令生命周期的持有者，并在 `scripts/` 内提取最小共享状态实现；`composer check` 的所有静态和测试阶段保持原范围与失败退出行为。
- 新格式凭据不得误信旧版本无法关联到一次执行的状态；升级后清楚提示重新检查。

M3 的修改范围是 finish hook、相关 hook 测试和对应工作流说明：
- 计划进入全部完成或从 active 移到 completed 时，独立验证当前代码的全量凭据；“本会话没有代码修改”不能跳过最终验收。
- 通过文字写入 `composer check pending`、`failed` 或声称 `passed`，都不能替代真实通过凭据。
- 覆盖上一会话局部通过并提交、下一会话只勾完/归档；也覆盖跨会话未提交的代码，以及全量通过后代码再次变化。
- 保留完整成功路径：当前代码全量通过后，跨会话完成记录和归档应成功。
- 计划草稿、普通文档修改、历史已完成计划的记录补充和只读检查不应被错误识别为新的代码交付；测试区分这些行为与当前计划首次完成。

M1、M2、M3 每个里程碑的检查命令：
```sh
php artisan test --compact tests/Unit/Hooks
composer check:milestone -- tests/Unit/Hooks tests/Structure
```
选择原因：runner、开始/结束 hook、已有失败处理、受保护文件规则和 Composer 命令结构都是直接受影响的行为或调用方。记录测试数量、首次失败证据、修复后结果及提交；只称为专项/里程碑通过。正在修改的通过标记不能作为唯一证据，必须同时查看实际测试退出码和输出。

M4 的最终检查：
```sh
CI=true composer check
CI=true composer check:reference
composer validate --no-check-publish
git diff --check
```
- 全量测试在实现完成时执行。若独立审查后又改代码，重新执行最终全量检查；若提前发现影响无法界定或涉及共享测试/数据库基础设施，则提前执行全量检查并在 Progress 解释原因。
- 按 `docs/prompts/review.md` 请求独立 reviewer，最多三轮，记录并修复 P0/P1；重新覆盖本次三个复现和相邻边界，不能只看已有测试是否变绿。
- 在 elelong-auction 的隔离副本验证修复后的工具与其 Composer 配置和 hook 配置兼容，运行相关 hook/structure 测试；兼容性结果不称为 auction 全量业务验收。直接升级 auction 的开发分支不属于本轮母板实施方案。
- 发布目标为 v1.1.1；实际发布前检查该版本是否已存在。推送明确的分支和新标签，验证发布提交的 GitHub CI，并核对 Packagist stable 的 source/dist 与发布提交一致、dev-main 与最终 main 一致。
- 更新 `docs/QUALITY.md`，填写验收证据并归档本计划。没有页面变化，UI 检查不适用。

## Progress
- 2026-11-10 M2：新增 13 项初始场景中 11 项失败、2 项既有行为通过；随后增加缺失运行身份和完整套件结束凭据覆盖。修复后 `CI=true composer check:milestone -- tests/Unit/Hooks tests/Structure` 全部静态检查及 112 项 / 317 断言通过。全量入口保留 static → 全部 JS → 全部 PHP；共有生命周期锁、独立运行身份、成功完成状态及版本化凭据阻止串用，进程终止和不同 worktree 并行已验证。
- 2026-11-10 M1：新增 9 项回归先全部失败；修复后 Hooks 61 项 / 221 断言通过。`CI=true composer check:milestone -- tests/Unit/Hooks tests/Structure` 通过全量静态检查及 97 项 / 258 断言；只代表本阶段受影响范围通过。开始任一检查都撤销两类旧通过记录，无效参数也先撤销。
- 2026-11-10 16:47 +08:00 用户要求实施计划。已读取当前工作流、计划格式、现有 runner/hook、Composer 编排和相关测试；母板无未提交代码修改，active 目录没有其他进行中的计划。
- 2026-11-10 16:47 +08:00 前一轮只读审计：auction 的 `MilestoneCheckTest.php` 25 项 / 80 断言通过，`FinishCheckTest.php` 25 项 / 89 断言通过；另外三个控制流程缺口均在临时仓库复现。未执行全量业务套件，两个项目源码未改动。
- 2026-11-10 16:47 +08:00 已应用 testing-best-practices 的测试覆盖、隔离与审查要求。当前只编写计划，实施里程碑均未开始。

## Surprises & Discoveries
- auction 的参数重试修复已正确提前清除旧局部通过记录，但共享开始记录仍会被重叠命令覆盖。只合入调用顺序调整不足以解决整条检查流程的问题。
- 两个项目的 `scripts/hooks/finish-check.php` 在审计时相同；跨会话和全量失败后的缺口需从母板修复。
- `composer check` 当前由多个独立子命令组成，单独在开始 hook 内加一个短锁无法保护整个执行周期。并发保护必须覆盖完整命令生命周期。
- 现有完整标记和局部标记分离是正确的；缺失的是一致的失效规则、单次执行归属和跨会话最终验收触发条件。

## Decision Log
- 2026-11-10 M2：composer.json 的 check 改由 scripts/check.php 持有全程锁；tests/Structure/ConfigTest.php 的命令编排预期随批准的入口变化更新，完整阶段及参数由真实 runner 子进程回归验证，原有其他静态配置断言保留。FinishCheckTest 的辅助执行器使用同一 CheckRun 生命周期，原有业务/失败断言未修改。
- 2026-11-10 M1 提交 eed130e。M2 将现有临时仓库帮助函数原样移动到 tests/Support/MilestoneWorkspace.php，让新增并发测试与已有测试共用同一 fixture；这是已批准测试实现范围内的必要提取，不改变原有断言。共享实现具体文件为 scripts/check-run.php 和 scripts/check-process.php，并新增 tests/Unit/Hooks/CheckRunTest.php。
- 2026-11-10 用户回复“开始”，批准本计划列明的受保护文件与实现范围，包括 scripts/check-milestone.php、scripts/hooks/finish-check.php、scripts/check.php、scripts/ 下的最小共享实现、composer.json、tests/Unit/Hooks/FinishCheckTest.php、tests/Unit/Hooks/MilestoneCheckTest.php、新增 tests/Unit/Hooks/*Test.php、tests/Structure/ConfigTest.php、docs/workflow.md、docs/golden-principles.md。提交、推送和 Packagist 发布沿用已有授权。
- 2026-11-10 本次仅新增本计划文件；用户要求先看计划，未开始实现或发布。此前对提交、推送与 Packagist 的授权保留，不重复请求同一授权。
- 2026-11-10 拟改的受保护路径明确列为：`scripts/check-milestone.php`、`scripts/hooks/finish-check.php`、`scripts/check.php`（如新增）、`scripts/` 内为共享检查生命周期新增的最小实现、`composer.json`、`tests/Unit/Hooks/FinishCheckTest.php`、`tests/Unit/Hooks/MilestoneCheckTest.php`、必要的新增 `tests/Unit/Hooks/*Test.php`、`tests/Structure/ConfigTest.php`，以及为准确解释新增边界而需要更新的 `docs/workflow.md` 和 `docs/golden-principles.md`。这些是当前提案的文件范围，不是已经发生的修改。
- 2026-11-10 既有结构测试如果硬编码旧的命令组合，只调整编排预期以反映新的生命周期，保留并加强所有检查阶段不可减少的约束；不放宽 PHPStan、oxlint、测试套件或业务断言，不增加依赖。
- 2026-11-10 计划先修母板，再用隔离副本核对 auction 兼容性；不自动修改 auction 的业务代码、数据库重建策略或工作分支。

## Outcomes & Retrospective
计划已准备，尚未实施。最终交付需包含三个缺口及原重试问题的回归结果、完整检查输出、独立审查结论、发布提交和 Packagist 对应版本，不能用本次审计或专项测试结果替代这些验收证据。
