<?php

namespace App\Policies;

use App\Models\User;

/**
 * Who may change an account: only its owner. The settings pages only ever
 * pass the signed-in user, but every endpoint still asks (docs/rules/controllers.md).
 */
class UserPolicy
{
    public function update(User $user, User $model): bool
    {
        return $user->is($model);
    }

    /**
     * The language switch changes only the caller's own preference, so everyone may use it, guests included.
     */
    public function updateLocale(?User $user): bool
    {
        return true;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->is($model);
    }
}
