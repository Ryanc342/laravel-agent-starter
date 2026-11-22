<?php

namespace App\Jobs;

use App\Actions\Operation;
use App\Actions\OutsideCall;
use App\Actions\UserlessOperation;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use LogicException;

/**
 * The background job part: one job runs one operation, with every step of
 * the operation part (the permission is checked again when it runs). An
 * operation starts it with later(), once its writes are saved; a schedule
 * starts it with bySystem() (routes/console.php).
 *
 * A failed job is not tried again and stays in the failed jobs list
 * (php artisan queue:failed), unless the operation says running it twice
 * does no harm (repeatable()): then it is tried up to 3 times.
 */
final class OperationJob implements ShouldQueue
{
    use Queueable;

    public int $tries;

    /** @var list<int> seconds to wait before the second and the third try */
    public array $backoff = [10, 60];

    /**
     * @param  class-string<Operation|UserlessOperation>  $operation
     * @param  array<string, mixed>  $input  plain values only: records are found again when it runs
     */
    public function __construct(
        public string $operation,
        public ?User $user = null,
        public ?Model $subject = null,
        public array $input = [],
    ) {
        $instance = app($operation);

        if ($user === null && $instance instanceof Operation) {
            throw new LogicException("{$operation} needs a user: only a UserlessOperation runs as the system.");
        }

        array_walk_recursive($input, fn (mixed $value) => is_object($value)
            ? throw new LogicException('A background job gets plain values as input, not objects: pass the key of a record, and name the field in records().')
            : null);

        $this->tries = $instance->repeatable() ? 3 : 1;
    }

    /**
     * A job the system runs, for the schedule.
     *
     * @param  class-string<UserlessOperation>  $operation
     * @param  array<string, mixed>  $input
     */
    public static function bySystem(string $operation, ?Model $subject = null, array $input = []): self
    {
        return new self($operation, null, $subject, $input);
    }

    public function handle(): void
    {
        $operation = app($this->operation);

        OutsideCall::insideBackgroundJob(function () use ($operation): void {
            if ($operation instanceof Operation) {
                $operation->run($this->user ?? throw new LogicException("{$this->operation} needs a user."), $this->subject, $this->input);
            } elseif ($this->user === null) {
                $operation->runBySystem($this->subject, $this->input);
            } else {
                $operation->run($this->user, $this->subject, $this->input);
            }
        });
    }
}
