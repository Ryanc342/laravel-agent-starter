<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Notifications\AccountCreated;
use Illuminate\Support\Facades\DB;

/**
 * A guest creates an account. Fortify's registration route runs it through
 * App\Actions\Fortify\CreateNewUser, which checks the input first.
 */
final class RegisterUser
{
    public function handle(string $name, string $email, string $password): User
    {
        return DB::transaction(function () use ($name, $email, $password): User {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);
            $user->notify(new AccountCreated);

            return $user;
        });
    }
}
