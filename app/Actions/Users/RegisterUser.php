<?php

namespace App\Actions\Users;

use App\Actions\UserlessOperation;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Notifications\AccountCreated;
use Illuminate\Database\Eloquent\Model;

/**
 * A guest creates an account. Fortify's registration route runs it through
 * App\Http\Fortify\CreateNewUser.
 */
final class RegisterUser extends UserlessOperation
{
    use PasswordValidationRules, ProfileValidationRules;

    public function ability(): string
    {
        return 'register';
    }

    public function subjectClass(): string
    {
        return User::class;
    }

    public function rules(): array
    {
        return [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ];
    }

    public function inputRefusal(?Model $subject, array $input): ?array
    {
        return User::emailTaken((string) $input['email'])
            ? ['email' => __('The email has already been taken.')]
            : null;
    }

    protected function perform(?Model $subject, array $input): User
    {
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
        $user->notify(new AccountCreated);

        return $user;
    }
}
