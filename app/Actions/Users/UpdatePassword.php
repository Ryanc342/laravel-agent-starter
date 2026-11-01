<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Notifications\PasswordChanged;
use Illuminate\Support\Facades\DB;

/**
 * Two entry points set a password: the security settings page and Fortify's
 * password reset. Both call this Action, so both notify the user.
 */
final class UpdatePassword
{
    public function handle(User $user, string $password): void
    {
        DB::transaction(function () use ($user, $password) {
            $user->forceFill([
                'password' => $password,
            ])->save();

            $user->notify(new PasswordChanged);
        });
    }
}
