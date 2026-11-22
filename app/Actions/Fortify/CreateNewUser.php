<?php

namespace App\Actions\Fortify;

use App\Actions\Users\RegisterUser;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Fortify's registration controller calls this class (registered in
 * FortifyServiceProvider). It checks the permission and the input, then
 * RegisterUser creates the account.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private RegisterUser $registerUser) {}

    /**
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Gate::authorize('register', User::class);

        $validated = Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return $this->registerUser->handle($validated['name'], $validated['email'], $validated['password']);
    }
}
