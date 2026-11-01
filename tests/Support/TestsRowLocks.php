<?php

namespace Tests\Support;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;

/**
 * For a test of a row lock (docs/rules/tests.md, "Row locks"). It plays a second user
 * saving at the same moment, on a second connection to the test database.
 *
 * A second connection only sees committed rows, and RefreshDatabase keeps every row of
 * a test uncommitted. So a test file that uses this trait instead of RefreshDatabase
 * commits its rows (DatabaseTruncation) and empties every table after each test, so no
 * row reaches the tests that follow.
 */
trait TestsRowLocks
{
    use DatabaseTruncation;

    /**
     * Runs $write on the second connection and says whether it had to wait for a lock.
     * MySQL gives up waiting after one second (error 1205); any other error is thrown.
     *
     * While $write runs, the second connection is the default one, so Actions and
     * factories called inside it use the second connection. Load the records it
     * changes inside $write: a record loaded before keeps the first connection.
     */
    protected function waitsForLock(Closure $write): bool
    {
        $first = DB::getDefaultConnection();

        config(['database.connections.second' => config("database.connections.{$first}")]);
        DB::connection('second')->statement('SET SESSION innodb_lock_wait_timeout = 1');
        DB::setDefaultConnection('second');

        try {
            $write();
        } catch (QueryException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1205) {
                return true;
            }

            throw $exception;
        } finally {
            DB::setDefaultConnection($first);
        }

        return false;
    }

    /** Called by Laravel after each test that uses this trait. */
    protected function tearDownTestsRowLocks(): void
    {
        DB::purge('second');
        $this->truncateDatabaseTables();
    }
}
