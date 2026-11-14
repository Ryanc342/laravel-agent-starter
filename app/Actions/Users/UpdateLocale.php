<?php

namespace App\Actions\Users;

use App\Enums\Locale;
use App\Models\User;

/**
 * Saves the language a signed-in user chose, so it follows them to another
 * browser. A guest has no user to save it on: their choice lives in the
 * `locale` cookie only (LocaleController).
 */
final class UpdateLocale
{
    public function handle(?User $user, Locale $locale): void
    {
        $user?->update(['locale' => $locale]);
    }
}
