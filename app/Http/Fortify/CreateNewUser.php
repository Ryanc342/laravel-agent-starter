<?php

namespace App\Http\Fortify;

use App\Actions\Users\CreateUser;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Fortify's registration controller calls this class (registered in
 * FortifyServiceProvider). It plays the controller role: check the input
 * shape, then call one Action. Fortify's routes already decide who may call
 * it (guests only, a valid reset token), so there is no Gate check here.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private CreateUser $createUser) {}

    /**
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $data = Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return $this->createUser->handle($data);
    }
}
