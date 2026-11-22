<?php

namespace App\Actions;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use LogicException;
use Throwable;

/**
 * The outside-service part: the only way to call another system (payment,
 * SMS, exchange rates). Each service's address, token and time limit are
 * in config/services.php under its name. Every call has a time limit and
 * an error answer is an exception, never a silent empty result.
 *
 *   read()   a call that only reads: before the operation's transaction
 *            (the operation's prepare()); sent once more after a network failure.
 *   change() a call that changes something on the other side: only in a
 *            background job, after the writes are saved (the operation's
 *            afterCommit()); never sent twice by itself.
 *
 * In tests every call must be faked (Http::fake()): a real one fails
 * (Tests\TestCase prevents stray requests).
 */
final class OutsideCall
{
    /** How many background jobs are running an operation right now (App\Jobs\OperationJob). */
    private static int $backgroundJobs = 0;

    public static function read(string $service): PendingRequest
    {
        self::refuseInsideTransaction('read');

        return self::client($service)
            ->retry(2, 200, fn (Throwable $error): bool => $error instanceof ConnectionException);
    }

    public static function change(string $service): PendingRequest
    {
        self::refuseInsideTransaction('change');

        if (self::$backgroundJobs === 0) {
            throw new LogicException('A call that changes something on another system runs only in a background job, in the afterCommit() of the operation the job runs (App\Jobs\OperationJob).');
        }

        return self::client($service);
    }

    /**
     * Runs $run as a background job's work: only there may change() be called.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $run
     * @return TResult
     */
    public static function insideBackgroundJob(Closure $run): mixed
    {
        self::$backgroundJobs++;

        try {
            return $run();
        } finally {
            self::$backgroundJobs--;
        }
    }

    private static function client(string $service): PendingRequest
    {
        $settings = config("services.{$service}");

        if (! is_array($settings) || ! is_string($settings['url'] ?? null) || $settings['url'] === '') {
            throw new LogicException("config/services.php has no url for the outside service [{$service}].");
        }

        $timeout = $settings['timeout'] ?? 10;
        $request = Http::baseUrl($settings['url'])
            ->timeout(is_int($timeout) ? $timeout : 10)
            ->connectTimeout(5)
            ->acceptJson()
            ->throw();
        $token = $settings['token'] ?? null;

        return is_string($token) && $token !== '' ? $request->withToken($token) : $request;
    }

    private static function refuseInsideTransaction(string $kind): void
    {
        // The transactions the application opened (a test's own wrapping transaction is not one of them).
        if (app('db.transactions')->callbackApplicableTransactions()->isNotEmpty()) {
            throw new LogicException("An outside call ({$kind}) never runs inside a database transaction: a read goes in the operation's prepare(), a change in its afterCommit().");
        }
    }
}
