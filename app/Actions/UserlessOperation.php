<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * The operation part, for a business operation that does not depend on who
 * does it: one a guest may do (registration, a password reset by link) or one
 * the system does on a schedule. Same steps as
 * Operation (OperationSteps); the operation's own methods get no user.
 *
 * A guest passes the permission step only when the Policy ability accepts a
 * missing user (its user parameter is ?User). runBySystem() skips the
 * permission steps; only schedules and commands may call it (PHPStan).
 */
abstract class UserlessOperation
{
    use OperationSteps;

    /**
     * Why the operation cannot run now, as field => message; null when it can.
     *
     * @return array<string, string>|null
     */
    public function refusal(?Model $subject): ?array
    {
        return null;
    }

    /**
     * Why it cannot run with this input, as field => message; null when it can.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, string>|null
     */
    public function inputRefusal(?Model $subject, array $input): ?array
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
    public function prepare(?Model $subject, array $input): array
    {
        return [];
    }

    /**
     * The writes of the operation, inside the transaction.
     *
     * @param  array<string, mixed>  $input
     */
    abstract protected function perform(?Model $subject, array $input): mixed;

    /**
     * What happens once the writes are saved: the place for a call that changes
     * something on the other side (App\Actions\OutsideCall::change()), in an
     * operation a background job runs. Not called when the operation was refused or undone.
     *
     * @param  array<string, mixed>  $input
     */
    public function afterCommit(?Model $subject, array $input, mixed $result): void {}

    /**
     * What happens after success: back to the page by default.
     */
    public function after(mixed $result): After
    {
        return After::back();
    }

    /**
     * Runs every step; the permission is checked for $user, who may be a guest.
     *
     * @param  array<string, mixed>  $input
     */
    final public function run(?User $user, ?Model $subject = null, array $input = []): mixed
    {
        return $this->runAs($user, true, $subject, $input);
    }

    /**
     * Runs every step except the permission steps: the system does it.
     *
     * @param  array<string, mixed>  $input
     */
    final public function runBySystem(?Model $subject = null, array $input = []): mixed
    {
        return $this->runAs(null, false, $subject, $input);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function runAs(?User $user, bool $checkPermission, ?Model $subject, array $input): mixed
    {
        return $this->runSteps(
            $user,
            $checkPermission,
            $subject,
            $input,
            fn (?Model $subject, array $input): array => $this->prepare($subject, $input),
            fn (?Model $subject): ?array => $this->refusal($subject),
            fn (?Model $subject, array $input): ?array => $this->inputRefusal($subject, $input),
            fn (?Model $subject, array $input): mixed => $this->perform($subject, $input),
            fn (?Model $subject, array $input, mixed $result) => $this->afterCommit($subject, $input, $result),
        );
    }
}
