<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Users\DeleteUser;
use App\Actions\Users\UpdateProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        Gate::authorize('update', $user);

        return Inertia::render('settings/profile', [
            'emailNeedsVerification' => $user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail(),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, UpdateProfile $updateProfile): RedirectResponse
    {
        Gate::authorize('update', $request->user());

        $updateProfile->handle($request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(ProfileDeleteRequest $request, DeleteUser $deleteUser): RedirectResponse
    {
        $user = $request->user();

        Gate::authorize('delete', $user);

        Auth::logout();

        $deleteUser->handle($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
