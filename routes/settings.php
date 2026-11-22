<?php

use App\Actions\Users\ChangePassword;
use App\Actions\Users\DeletePasskey;
use App\Actions\Users\DeleteUser;
use App\Actions\Users\UpdateProfile;
use App\Http\Controllers\Settings\PasskeyEndpointsController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile')->name('settings');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::operation('settings/profile', UpdateProfile::class)->name('profile.update');
    Route::operation('settings/profile/delete', DeleteUser::class)->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::operation('settings/password', ChangePassword::class)
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::operation('settings/passkeys/{passkey}/delete', DeletePasskey::class)
        ->middleware('password.confirm')
        ->name('passkeys.destroy');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');
});

Route::get('.well-known/passkey-endpoints', PasskeyEndpointsController::class)->name('well-known.passkeys');
