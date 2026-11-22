<?php

namespace App\Actions\Users;

use App\Actions\After;
use App\Actions\Operation;
use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * The signed-in user changes their password on the security page, after
 * typing the current one. The new password is set by ResetPassword, so both
 * ways of setting a password write and notify the same way.
 */
final class ChangePassword extends Operation
{
    use PasswordValidationRules;

    public function __construct(private ResetPassword $resetPassword) {}

    public function ability(): string
    {
        return 'updatePassword';
    }

    public function subjectClass(): string
    {
        return User::class;
    }

    public function rules(): array
    {
        return [
            'current_password' => $this->currentPasswordRules(),
            'password' => $this->passwordRules(),
        ];
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        return $this->resetPassword->run($user, $user, [
            'password' => $input['password'],
            'password_confirmation' => $input['password'],
        ]);
    }

    public function after(mixed $result): After
    {
        return After::back(__('Password updated.'));
    }
}
