<?php

use App\Actions\Tasks\DeleteTask;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a task can be deleted', function () {
    $task = Task::factory()->create();

    app(DeleteTask::class)->handle($task);

    $this->assertModelMissing($task);
});

test('a completed task can be deleted too', function () {
    $task = Task::factory()->completed()->create();

    app(DeleteTask::class)->handle($task);

    $this->assertModelMissing($task);
});
