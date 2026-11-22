<?php

namespace App\Http\Resources;

/**
 * `auth` in every page's shared data: the signed-in user, null for a guest.
 */
final class SharedAuth extends ResourceShape
{
    public function __construct(
        public ?UserResource $user,
    ) {}
}
