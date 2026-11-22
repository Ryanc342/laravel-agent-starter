<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Users\DeleteUser;
use App\Actions\Users\UpdateProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Fortify;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        Gate::authorize('updateProfile', User::class);

        $user = $request->user();

        return Inertia::render('settings/profile', [
            'user' => UserResource::from($user, $user),
            'emailNeedsVerification' => $user->needsEmailVerification(),
            'verificationLinkSent' => $request->session()->get('status') === Fortify::VERIFICATION_LINK_SENT,
            'can' => [
                // The same check DeleteUser makes, so the button shows exactly when deleting succeeds.
                'delete_account' => $user->can('deleteAccount', User::class) && ! $user->needsEmailVerification(),
            ],
        ]);
    }

    /**
     * Save the user's name and email address.
     */
    public function update(ProfileUpdateRequest $request, UpdateProfile $updateProfile): RedirectResponse
    {
        $updateProfile->handle($request->user(), $request->validated('name'), $request->validated('email'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's account and sign them out.
     */
    public function destroy(ProfileDeleteRequest $request, DeleteUser $deleteUser): RedirectResponse
    {
        $deleteUser->handle($request->user());

        // This device only: logout() would also save a new remember token on the
        // user, who has just been deleted.
        Auth::guard('web')->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }
}
