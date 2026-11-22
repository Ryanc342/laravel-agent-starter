<?php

namespace App\Models;

use Database\Factories\PasskeyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Passkeys\Passkey as PackagePasskey;

/**
 * One of a user's passkeys: the laravel/passkeys model with a UUID key.
 * FortifyServiceProvider tells the package to use this class.
 *
 * @property string $id
 * @property string $user_id
 */
class Passkey extends PackagePasskey
{
    /** @use HasFactory<PasskeyFactory> */
    use HasFactory, HasUuids;

    /**
     * Whether this passkey belongs to the user.
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->getKey();
    }
}
