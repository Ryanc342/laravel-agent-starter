<?php

namespace App\Policies;

use App\Models\Passkey;
use App\Models\User;

/**
 * Who may act on a passkey: only the user it belongs to.
 */
class PasskeyPolicy
{
    public function delete(User $user, Passkey $passkey): bool
    {
        return $passkey->isOwnedBy($user);
    }
}
