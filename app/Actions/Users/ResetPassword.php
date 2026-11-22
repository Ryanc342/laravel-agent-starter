<?php

namespace App\Actions\Users;

use App\Actions\UserlessOperation;
use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Notifications\PasswordChanged;
use Illuminate\Database\Eloquent\Model;

/**
 * Sets a new password for the user in $subject and tells them. Fortify's
 * password reset by link runs it through App\Http\Fortify\ResetUserPassword,
 * after Fortify has checked the link; ChangePassword runs it for the signed-in user.
 */
final class ResetPassword extends UserlessOperation
{
    use PasswordValidationRules;

    public function ability(): string
    {
        return 'resetPassword';
    }

    public function subjectClass(): string
    {
        return User::class;
    }

    public function rules(): array
    {
        return [
            'password' => $this->passwordRules(),
        ];
    }

    protected function perform(?Model $subject, array $input): mixed
    {
        assert($subject instanceof User);
        $subject->forceFill(['password' => $input['password']])->save();
        $subject->notify(new PasswordChanged);

        return null;
    }
}
