<?php

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('every page receives the signed-in user through UserResource only', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.id', $user->id)
            ->where('auth.user.email', $user->email)
            ->missing('auth.user.password')
            ->missing('auth.user.two_factor_secret')
            ->missing('auth.user.two_factor_confirmed_at'),
        );
});

test('guests receive no user and no unread notifications', function () {
    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user', null)
            ->where('unreadNotifications', 0),
        );
});

test('every page receives the number of the user\'s unread notifications', function () {
    $user = User::factory()->create();
    Notification::factory()->for($user, 'notifiable')->count(2)->create();
    Notification::factory()->for($user, 'notifiable')->read()->create();
    Notification::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('unreadNotifications', 2));
});
