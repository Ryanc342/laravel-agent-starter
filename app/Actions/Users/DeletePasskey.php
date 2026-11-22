<?php

namespace App\Actions\Users;

use App\Actions\After;
use App\Actions\Operation;
use App\Models\Passkey;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passkeys\Events\PasskeyDeleted;

/**
 * The signed-in user deletes one of their passkeys on the security page.
 * Like the passkeys package, it announces PasskeyDeleted.
 */
final class DeletePasskey extends Operation
{
    public function ability(): string
    {
        return 'delete';
    }

    public function subjectClass(): string
    {
        return Passkey::class;
    }

    protected function perform(User $user, ?Model $subject, array $input): mixed
    {
        assert($subject instanceof Passkey);
        $subject->delete();

        PasskeyDeleted::dispatch($user, $subject);

        return null;
    }

    public function after(mixed $result): After
    {
        return After::back(__('Passkey deleted.'));
    }
}
