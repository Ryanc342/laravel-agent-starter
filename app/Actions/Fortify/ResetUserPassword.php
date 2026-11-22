<?php

namespace App\Actions\Fortify;

use App\Actions\Users\ResetPassword;
use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

/**
 * Fortify's password reset controller calls this class (registered in
 * FortifyServiceProvider) once it has checked the reset link. It checks the
 * permission and the new password, then ResetPassword sets it.
 */
class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function __construct(private ResetPassword $resetPassword) {}

    /**
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Gate::authorize('resetPassword', $user);

        $validated = Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $this->resetPassword->handle($user, $validated['password']);
    }
}
