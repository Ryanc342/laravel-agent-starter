<?php

namespace App\Http\Resources;

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use UnexpectedValueException;

/**
 * One of the user's passkeys, as the security page lists it.
 */
final class PasskeyResource extends ResourceData
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $authenticator,
        public string $created_at_diff,
        public ?string $last_used_at_diff,
    ) {}

    public static function from(Model $record, ?User $viewer): static
    {
        assert($record instanceof Passkey);

        return new self(
            id: $record->id,
            name: $record->name,
            authenticator: $record->authenticator,
            created_at_diff: ($record->created_at ?? throw new UnexpectedValueException("Passkey {$record->id} has no creation time."))->diffForHumans(),
            last_used_at_diff: $record->last_used_at?->diffForHumans(),
        );
    }
}
