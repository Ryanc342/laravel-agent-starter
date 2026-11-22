<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Users\DeleteUser;
use App\Http\Controllers\Controller;
use App\Http\OperationGate;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Fortify;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page. Saving it and deleting the
     * account are operations (routes/settings.php).
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
                'delete_account' => OperationGate::allows($user, DeleteUser::class),
            ],
        ]);
    }
}
