<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Notifications\AccountCreated;
use Illuminate\Support\Facades\DB;

final class CreateUser
{
    /**
     * @param  array<string, mixed>  $data  validated input: name, email, password
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $user->notify(new AccountCreated);

            return $user;
        });
    }
}
