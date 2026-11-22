<?php

namespace App\Http\Resources;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * `auth` in every page's shared data: the signed-in user, null for a guest.
 */
#[TypeScript]
final class SharedAuth extends ResourceShape
{
    public function __construct(
        public ?UserResource $user,
    ) {}
}
