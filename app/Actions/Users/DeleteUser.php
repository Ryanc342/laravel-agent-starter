<?php

namespace App\Actions\Users;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The user deletes their account; only a verified account can be deleted.
 * Notifications are linked to their user without a foreign key, so they are
 * deleted here; passkeys are removed by the database (cascade on delete).
 */
final class DeleteUser
{
    public function handle(User $user): void
    {
        if ($user->needsEmailVerification()) {
            throw ValidationException::withMessages([
                'account' => __('Verify your email address before you delete your account.'),
            ]);
        }

        DB::transaction(function () use ($user): void {
            Notification::query()->ownedBy($user)->delete();
            $user->delete();
        });
    }
}
