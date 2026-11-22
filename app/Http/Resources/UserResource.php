<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The signed-in user, shared with every page as `auth.user`
 * (HandleInertiaRequests): only what the pages show.
 */
#[TypeScript]
final class UserResource extends ResourceData
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $initials,
    ) {}

    public static function from(Model $record, ?User $viewer): static
    {
        assert($record instanceof User);

        return new self(
            id: $record->id,
            name: $record->name,
            email: $record->email,
            initials: Str::initials($record->name, capitalize: true),
        );
    }
}
