<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Laravel\Passkeys\Passkey;
use ParagonIE\ConstantTime\Base64UrlSafe;

/** @extends Factory<Passkey> */
class PasskeyFactory extends Factory
{
    protected $model = Passkey::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Test passkey',
            'credential_id' => Base64UrlSafe::encodeUnpadded(random_bytes(32)),
            'credential' => [],
        ];
    }
}
