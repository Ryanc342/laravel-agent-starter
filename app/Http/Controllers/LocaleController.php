<?php

namespace App\Http\Controllers;

use App\Actions\Users\UpdateLocale;
use App\Http\Requests\Locale\UpdateLocaleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

/**
 * The language switch: on Settings → Appearance and at the top right of the
 * guest pages. It saves the choice on the user when signed in, always sets the
 * `locale` cookie, and sends the user back, so the page comes back translated.
 * There is no record and no permission to check: it changes only the caller's
 * own preference, so the route is open to guests.
 */
class LocaleController extends Controller
{
    public function update(UpdateLocaleRequest $request, UpdateLocale $updateLocale): RedirectResponse
    {
        $locale = $request->locale();

        $updateLocale->handle($request->user(), $locale);

        return back()->withCookie(Cookie::forever('locale', $locale->value));
    }
}
