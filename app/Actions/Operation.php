<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * The operation part, for a business operation done by a signed-in user.
 * An operation declares what it is; the part runs every step in one fixed
 * order (OperationSteps), so no operation can skip a step or reorder them:
 *
 *   permission -> input shape -> records named in the input -> prepare
 *   -> one transaction: lock -> refusal -> refusal for this input -> writes
 *   -> once saved: afterCommit
 *
 * An operation that runs without a user (registration, a guest's choice, a
 * schedule) extends UserlessOperation instead. The same refusal() also answers
 * "can this user do it now?" for the page's buttons (App\Http\OperationGate),
 * so the button and the operation can never disagree.
 */
abstract class Operation
{
    use OperationSteps;

    /**
     * Why the operation cannot run now, as field => message; null when it can.
     * Inside the transaction it sees the locked record as stored now.
     *
     * @return array<string, string>|null
     */
    public function refusal(User $user, ?Model $subject): ?array
    {
        return null;
    }

    /**
     * Why it cannot run with this input, as field => message; null when it
     * can. Checked after refusal().
     *
     * @param  array<string, mixed>  $input
     * @return array<string, string>|null
     */
    public function inputRefusal(User $user, ?Model $subject, array $input): ?array
    {
        return null;
    }

    /**
     * Reads from outside services the operation needs (App\Actions\OutsideCall::read()),
     * before the transaction starts; what it returns is added to the input.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function prepare(User $user, ?Model $subject, array $input): array
    {
        return [];
    }

    /**
     * The writes of the operation, inside the transaction.
     *
     * @param  array<string, mixed>  $input
     */
    abstract protected function perform(User $user, ?Model $subject, array $input): mixed;

    /**
     * What happens once the writes are saved: the place for a call that changes
     * something on the other side (App\Actions\OutsideCall::change()), in an
     * operation a background job runs. Not called when the operation was refused or undone.
     *
     * @param  array<string, mixed>  $input
     */
    public function afterCommit(User $user, ?Model $subject, array $input, mixed $result): void {}

    /**
     * What happens after success: back to the page by default.
     */
    public function after(mixed $result): After
    {
        return After::back();
    }

    /**
     * Runs every step for this user. Called by the HTTP part, a Fortify adapter,
     * a command, a job, and an operation that needs another operation.
     *
     * @param  array<string, mixed>  $input
     */
    final public function run(User $user, ?Model $subject = null, array $input = []): mixed
    {
        return $this->runSteps(
            $user,
            true,
            $subject,
            $input,
            fn (?Model $subject, array $input): array => $this->prepare($user, $subject, $input),
            fn (?Model $subject): ?array => $this->refusal($user, $subject),
            fn (?Model $subject, array $input): ?array => $this->inputRefusal($user, $subject, $input),
            fn (?Model $subject, array $input): mixed => $this->perform($user, $subject, $input),
            fn (?Model $subject, array $input, mixed $result) => $this->afterCommit($user, $subject, $input, $result),
        );
    }
}
