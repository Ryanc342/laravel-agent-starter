<?php

namespace App\Actions\Users;

use App\Models\Passkey;
use App\Models\User;
use Laravel\Passkeys\Events\PasskeyDeleted;

/**
 * The user deletes one of their passkeys on the security page.
 * Like the passkeys package, it announces PasskeyDeleted.
 */
final class DeletePasskey
{
    public function handle(User $user, Passkey $passkey): void
    {
        $passkey->delete();

        PasskeyDeleted::dispatch($user, $passkey);
    }
}
