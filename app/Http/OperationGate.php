<?php

namespace App\Http;

use App\Actions\Operation;
use App\Actions\UserlessOperation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * "Can this user run this operation now?" (part of the operation part). A
 * page's can.* flag asks this, and the operation checks the same two things
 * when it runs, so a button is shown exactly when pressing it succeeds
 * (as far as it does not depend on what the user types).
 */
final class OperationGate
{
    /**
     * @param  class-string<Operation|UserlessOperation>  $operation
     */
    public static function allows(?User $user, string $operation, ?Model $subject = null): bool
    {
        $instance = app($operation);
        $permitted = Gate::forUser($user)->allows($instance->ability(), $subject ?? $instance->subjectClass());

        if ($instance instanceof Operation) {
            return $user !== null && $permitted && $instance->refusal($user, $subject) === null;
        }

        return $permitted && $instance->refusal($subject) === null;
    }
}
