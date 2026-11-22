<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Notifications\EmailChanged;
use Illuminate\Support\Facades\DB;

/**
 * The signed-in user changes their name and email address. A changed address
 * is unverified until the user verifies it again, and the user is told.
 */
final class UpdateProfile
{
    public function handle(User $user, string $name, string $email): void
    {
        DB::transaction(function () use ($user, $name, $email): void {
            $user->fill([
                'name' => $name,
                'email' => $email,
            ]);

            $emailChanged = $user->isDirty('email');

            if ($emailChanged) {
                $user->email_verified_at = null;
            }

            $user->save();

            if ($emailChanged) {
                $user->notify(new EmailChanged($user->email));
            }
        });
    }
}
