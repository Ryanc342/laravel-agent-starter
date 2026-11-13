<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\CreateTask;
use App\Actions\Tasks\DeleteTask;
use App\Actions\Tasks\UpdateTask;
use App\Enums\TaskStatus;
use App\Http\Requests\Tasks\IndexTaskRequest;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    /**
     * Show the user's tasks, with the search, sort, filters and rows per page the user picked
     * in the URL (20 rows by default).
     */
    public function index(IndexTaskRequest $request): Response
    {
        Gate::authorize('viewAny', Task::class);

        $user = $request->user();

        $tasks = Task::query()
            ->ownedBy($user)
            ->matching($request->search())
            ->withStatuses($request->statuses())
            ->dueBetween($request->dueFrom(), $request->dueTo())
            ->sortedBy($request->sort(), $request->direction())
            ->paginate($request->perPage())
            ->withQueryString();

        return Inertia::render('tasks/index', [
            'tasks' => TaskResource::collection($tasks)->response($request)->getData(true),
            'statusOptions' => TaskStatus::options(),
            'can' => [
                'complete_overdue' => $user->can('completeOverdue', Task::class) && Task::hasOverdueFor($user),
            ],
        ]);
    }

    /**
     * Show one task, with its actions.
     */
    public function show(Request $request, Task $task): Response
    {
        Gate::authorize('view', $task);

        return Inertia::render('tasks/show', [
            'task' => TaskResource::make($task)->resolve($request),
        ]);
    }

    /**
     * Create a task.
     */
    public function store(StoreTaskRequest $request, CreateTask $createTask): RedirectResponse
    {
        Gate::authorize('create', Task::class);

        $createTask->handle($request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task created.')]);

        return to_route('tasks.index');
    }

    /**
     * Update a task.
     */
    public function update(UpdateTaskRequest $request, Task $task, UpdateTask $updateTask): RedirectResponse
    {
        Gate::authorize('update', $task);

        $updateTask->handle($task, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task updated.')]);

        return to_route('tasks.show', $task);
    }

    /**
     * Delete a task.
     */
    public function destroy(Task $task, DeleteTask $deleteTask): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $deleteTask->handle($task);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task deleted.')]);

        return to_route('tasks.index');
    }
}
