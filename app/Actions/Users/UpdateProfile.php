<?php

namespace App\Actions\Users;

use App\Actions\After;
use App\Actions\Operation;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Notifications\EmailChanged;
use Illuminate\Database\Eloquent\Model;

/**
 * The signed-in user changes their name and email address. A changed address
 * is unverified until the user verifies it again, and the user is told.
 */
final class UpdateProfile extends Operation
{
    use ProfileValidationRules;

    public function ability(): string
    {
        return 'updateProfile';
    }

    public function subjectClass(): string
    {
        return User::class;
    }

    public function rules(): array
    {
        return $this->profileRules();
    }

    public function inputRefusal(User $user, ?Model $subject, array $input): ?array
    {
        return User::emailTaken((string) $input['email'], except: $user)
            ? ['email' => __('The email has already been taken.')]
            : null;
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        $user->fill([
            'name' => $input['name'],
            'email' => $input['email'],
        ]);

        $emailChanged = $user->isDirty('email');

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->notify(new EmailChanged($user->email));
        }

        return null;
    }

    public function after(mixed $result): After
    {
        return After::toRoute('profile.edit', message: __('Profile updated.'));
    }
}
