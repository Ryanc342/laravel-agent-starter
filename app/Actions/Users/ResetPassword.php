<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Notifications\PasswordChanged;
use Illuminate\Support\Facades\DB;

/**
 * Sets a new password for the user and tells them. Both ways of setting a
 * password use it: the reset by link (App\Actions\Fortify\ResetUserPassword)
 * and the security page (SecurityController::update), so both write and
 * notify the same way.
 */
final class ResetPassword
{
    public function handle(User $user, string $password): void
    {
        DB::transaction(function () use ($user, $password): void {
            $user->forceFill(['password' => $password])->save();
            $user->notify(new PasswordChanged);
        });
    }
}
