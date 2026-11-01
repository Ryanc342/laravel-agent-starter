<?php

namespace App\Http\Fortify;

use App\Actions\Users\UpdatePassword;
use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

/**
 * Fortify's password reset controller calls this class (registered in
 * FortifyServiceProvider). It plays the controller role: check the input
 * shape, then call one Action. Fortify's routes already decide who may call
 * it (guests only, a valid reset token), so there is no Gate check here.
 */
class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function __construct(private UpdatePassword $updatePassword) {}

    /**
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        $data = Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $this->updatePassword->handle($user, $data['password']);
    }
}
