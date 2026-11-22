<?php

namespace App\Policies;

use App\Models\User;

/**
 * Who may change an account. The account operations act on the user who runs
 * them, so a signed-in user can only ever change their own account.
 */
class UserPolicy
{
    /**
     * Only someone without an account creates one.
     */
    public function register(?User $user): bool
    {
        return $user === null;
    }

    public function updateProfile(User $user): bool
    {
        return true;
    }

    public function updatePassword(User $user): bool
    {
        return true;
    }

    public function deleteAccount(User $user): bool
    {
        return true;
    }

    /**
     * A new password for $model: by link, Fortify has checked the link first;
     * on the security page, ChangePassword passes the signed-in user.
     */
    public function resetPassword(?User $user, User $model): bool
    {
        return $user === null || $user->is($model);
    }
}
