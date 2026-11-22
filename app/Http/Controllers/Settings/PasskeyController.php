<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Users\DeletePasskey;
use App\Http\Controllers\Controller;
use App\Models\Passkey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PasskeyController extends Controller
{
    /**
     * Delete one of the user's passkeys.
     */
    public function destroy(Request $request, Passkey $passkey, DeletePasskey $deletePasskey): RedirectResponse
    {
        Gate::authorize('delete', $passkey);

        $deletePasskey->handle($request->user(), $passkey);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Passkey deleted.')]);

        return back();
    }
}
