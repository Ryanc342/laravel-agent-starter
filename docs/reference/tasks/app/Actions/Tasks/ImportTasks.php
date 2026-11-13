<?php

namespace App\Actions\Tasks;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Imports the rows of a checked file: it calls CreateTask for each one, so the
 * open-task limit and every other rule of creating a task apply to an import
 * too, all in one transaction (every row or none). The rows were checked for
 * shape by ImportTaskRequest.
 */
final class ImportTasks
{
    public function __construct(private CreateTask $createTask) {}

    /**
     * @param  list<array{title: string, due_on: string|null}>  $rows
     * @return int the number of tasks created
     */
    public function handle(User $user, array $rows): int
    {
        return DB::transaction(function () use ($user, $rows): int {
            foreach ($rows as $row) {
                $this->createTask->handle($user, $row);
            }

            return count($rows);
        });
    }
}
