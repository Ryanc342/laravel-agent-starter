# Reference feature: Tasks

Example feature only. **Not part of the app**: nothing here is autoloaded, migrated, routed or built.
`composer check:reference` copies it into a temporary copy of the project and runs every check, so it stays correct.

It shows the architecture rules applied to one small feature (a to-do list).
When building a real feature, copy the _structure_ of the matching file here, then put your file at the same path without the `docs/reference/tasks/` prefix.

Do not copy the Task domain itself into the app, and do not "fix" files here without the user's approval.

## Where to look

| You need                                        | Open                                                                         |
| ----------------------------------------------- | ---------------------------------------------------------------------------- |
| Table                                           | `database/migrations/2026_10_24_000000_create_tasks_table.php`               |
| Status values and facts about them              | `app/Enums/TaskStatus.php` (`isOpen`, `openValues`)                          |
| A condition written once, asked by one record   | `app/Models/Task.php` (`ownedBy` and `isOwnedBy`, `overdue` and `isOverdue`) |
| Factory states                                  | `database/factories/TaskFactory.php`                                         |
| Separate permissions reusing one ownership fact | `app/Policies/TaskPolicy.php`                                                |
| Business operation with a rule                  | `app/Actions/Tasks/CreateTask.php`, `CompleteTask.php`                       |
| Operation reusing another                       | `app/Actions/Tasks/CompleteOverdueTasks.php`                                 |
| Shared field rules                              | `app/Concerns/TaskValidationRules.php`                                       |
| Input validation                                | `app/Http/Requests/Tasks/StoreTaskRequest.php`                               |
| Data for React                                  | `app/Http/Resources/TaskResource.php` and `resources/js/types/task.ts`       |
| CRUD controller                                 | `app/Http/Controllers/TaskController.php`                                    |
| Non-CRUD operation                              | `app/Http/Controllers/TaskCompletionController.php`                          |
| Routes                                          | `routes/tasks.php`                                                           |
| Page and components                             | `resources/js/pages/tasks/index.tsx`, `resources/js/components/tasks/`       |
| Paginated list                                  | `TaskController::index()` and `<Pagination>` in the page                     |
| Shared UI block                                 | `resources/js/components/tasks/task-fields.tsx` (create and edit forms)      |
| Tests                                           | `tests/Feature/Tasks/`                                                       |
| Buttons agree with the endpoints                | `tests/Feature/Tasks/TaskButtonsTest.php`                                    |

## Shared rules and their consumers

Each condition is written once, in one form. Every other place calls it.

| Agreed rule or fact                                  | Written once in                                    | Consumers                                                                |
| ---------------------------------------------------- | -------------------------------------------------- | ------------------------------------------------------------------------ |
| Which statuses still need work                       | `TaskStatus::isOpen()`; queries use `openValues()` | `Task::isOpen()`, `open()` scope, `listOrder()`                          |
| This task belongs to this user                       | `Task::ownedBy()` scope                            | `Task::isOwnedBy()`, `TaskPolicy`, index and bulk queries                |
| An open task with a due date before today is overdue | `Task::overdue()` scope                            | `Task::isOverdue()`, `Task::hasOverdueFor()`, `TaskResource`, bulk query |
| The user has something overdue to complete           | `Task::hasOverdueFor()`                            | page's `can.complete_overdue`, `CompleteOverdueTasks` refusal            |
| Only open tasks can be completed or edited           | `Task::canBeCompleted()`, `Task::canBeEdited()`    | `CompleteTask`, `UpdateTask`, `TaskResource` `can.*`                     |
| Complete one task                                    | `CompleteTask::handle()`                           | single completion endpoint and `CompleteOverdueTasks`                    |
| Which task operations this user may perform          | each `TaskPolicy` ability                          | controller authorization and the Resource's `can.*`                      |

Correct: `isOwnedBy()` asks the `ownedBy()` scope about one record, and update, delete and complete each call `$task->isOwnedBy($user)`. Wrong: writing `$task->user_id === $user->id` anywhere, because the scope already says it. Likewise, the overdue bulk completion calls `CompleteTask` instead of copying its checks and writes.

`TaskButtonsTest` checks that each button in `can.*` (the Resource's and the page's) is shown exactly when pressing it succeeds.
