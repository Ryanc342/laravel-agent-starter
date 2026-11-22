<?php

namespace App\Actions\Users;

use App\Actions\After;
use App\Actions\Operation;
use App\Concerns\PasswordValidationRules;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * The signed-in user deletes their account, after typing their password; only
 * a verified account can be deleted. Notifications are linked to their user
 * without a foreign key, so they are deleted here; passkeys are removed by the
 * database (cascade on delete). The user is signed out afterwards.
 */
final class DeleteUser extends Operation
{
    use PasswordValidationRules;

    public function ability(): string
    {
        return 'deleteAccount';
    }

    public function subjectClass(): string
    {
        return User::class;
    }

    public function rules(): array
    {
        return [
            'password' => $this->currentPasswordRules(),
        ];
    }

    public function refusal(User $user, ?Model $subject): ?array
    {
        return $user->needsEmailVerification()
            ? ['account' => __('Verify your email address before you delete your account.')]
            : null;
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        Notification::query()->ownedBy($user)->delete();
        $user->delete();

        return null;
    }

    public function after(mixed $result): After
    {
        return After::signOut();
    }
}
