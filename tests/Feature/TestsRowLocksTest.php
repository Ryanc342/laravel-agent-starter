<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\TestsRowLocks;

/*
| The template's check of tests/Support/TestsRowLocks.php, the helper for row-lock tests
| (docs/rules/tests.md, "Row locks"). A feature's lock test calls waitsForLock() at the
| moment its Action holds the lock, for example from a model event.
*/

uses(TestsRowLocks::class);

test('a write on the second connection waits for a row the test has locked', function () {
    $user = User::factory()->create(['name' => 'Before']);

    $waited = DB::transaction(function () use ($user) {
        User::query()->lockForUpdate()->findOrFail($user->id);

        return $this->waitsForLock(fn () => User::query()->findOrFail($user->id)->update(['name' => 'After']));
    });

    expect($waited)->toBeTrue()
        ->and($user->refresh()->name)->toBe('Before');
});

test('a write on the second connection sees the rows the test made and goes through when nothing is locked', function () {
    $user = User::factory()->create(['name' => 'Before']);

    expect($this->waitsForLock(fn () => User::query()->findOrFail($user->id)->update(['name' => 'After'])))->toBeFalse()
        ->and($user->refresh()->name)->toBe('After');
});

test('any other database error is thrown and the first connection is the default again', function () {
    $default = DB::getDefaultConnection();

    expect(fn () => $this->waitsForLock(fn () => DB::table('no_such_table')->count()))
        ->toThrow(QueryException::class);

    expect(DB::getDefaultConnection())->toBe($default);
});
