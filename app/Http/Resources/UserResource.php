<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * The signed-in user, shared with every page as `auth.user`
 * (HandleInertiaRequests): only what the pages show.
 */
final class UserResource extends ResourceData
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $initials,
    ) {}

    public static function from(User $record, ?User $viewer): self
    {
        return new self(
            id: $record->id,
            name: $record->name,
            email: $record->email,
            initials: Str::initials($record->name, capitalize: true),
        );
    }
}
