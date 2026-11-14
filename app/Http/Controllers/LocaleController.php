<?php

namespace App\Http\Controllers;

use App\Actions\Users\UpdateLocale;
use App\Http\Requests\Locale\UpdateLocaleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Gate;

/**
 * The language switch: on Settings → Appearance and at the top right of the
 * guest pages. It saves the choice on the user when signed in, always sets the
 * `locale` cookie, and sends the user back, so the page comes back translated.
 * It changes only the caller's own preference, so the Policy allows everyone,
 * guests included.
 */
class LocaleController extends Controller
{
    public function update(UpdateLocaleRequest $request, UpdateLocale $updateLocale): RedirectResponse
    {
        Gate::authorize('updateLocale', User::class);

        $locale = $request->locale();

        $updateLocale->handle($request->user(), $locale);

        return back()->withCookie(Cookie::forever('locale', $locale->value));
    }
}
