<?php

namespace App\Http\Fortify;

use App\Actions\Users\ResetPassword;
use App\Models\User;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

/**
 * Fortify's password reset controller calls this class (registered in
 * FortifyServiceProvider) once it has checked the reset link. It hands the
 * input to the ResetPassword operation for that user.
 */
class ResetUserPassword implements ResetsUserPasswords
{
    public function __construct(private ResetPassword $resetPassword) {}

    /**
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        $this->resetPassword->run(null, $user, $input);
    }
}
