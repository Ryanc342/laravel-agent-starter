<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Notifications\EmailChanged;
use Illuminate\Support\Facades\DB;

final class UpdateProfile
{
    /**
     * Business rule: a changed email address is unverified until the user
     * verifies it again, and the user is notified of the change.
     *
     * @param  array<string, mixed>  $data  validated input: name, email
     */
    public function handle(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
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
