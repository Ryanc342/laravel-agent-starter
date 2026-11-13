<?php

use App\Actions\Tasks\DeleteTasks;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('every task of the selection is deleted, open or completed, and no other', function () {
    $open = Task::factory()->create();
    $done = Task::factory()->completed()->create();
    $kept = Task::factory()->create();

    $deleted = app(DeleteTasks::class)->handle(new Collection([$open, $done]));

    expect($deleted)->toBe(2);
    $this->assertModelMissing($open);
    $this->assertModelMissing($done);
    $this->assertModelExists($kept);
});

test('with an empty selection it refuses', function () {
    $task = Task::factory()->create();

    expect(fn () => app(DeleteTasks::class)->handle(new Collection))->toThrow(ValidationException::class);

    $this->assertModelExists($task);
});

test('every task is deleted or none is', function () {
    $first = Task::factory()->create();
    $second = Task::factory()->create();

    Task::deleting(function (Task $task) use ($second) {
        if ($task->is($second)) {
            throw new RuntimeException('The second write failed.');
        }
    });

    expect(fn () => app(DeleteTasks::class)->handle(new Collection([$first, $second])))->toThrow(RuntimeException::class);

    $this->assertModelExists($first);
    $this->assertModelExists($second);
});
