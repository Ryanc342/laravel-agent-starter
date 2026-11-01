<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Passkeys\Passkey;

/**
 * One of the user's passkeys (the model comes from the laravel/passkeys
 * package). The data contract with the Passkey type in
 * resources/js/types/auth.ts. Change both together.
 *
 * @mixin Passkey
 */
class PasskeyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'authenticator' => $this->authenticator,
            'created_at_diff' => $this->created_at->diffForHumans(),
            'last_used_at_diff' => $this->last_used_at?->diffForHumans(),
        ];
    }
}
