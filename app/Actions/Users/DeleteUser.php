<?php

namespace App\Actions\Users;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DeleteUser
{
    /**
     * Notifications are linked to their user without a foreign key, so they are
     * deleted here. Passkeys are removed by the database (cascade on delete).
     */
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user) {
            Notification::query()->ownedBy($user)->delete();

            $user->delete();
        });
    }
}
