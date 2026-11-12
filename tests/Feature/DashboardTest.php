<?php

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('dashboard/index'));
});

test('guests see the welcome page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('welcome/index'));
});

test('the dashboard lists the user\'s newest notifications first, with the flags each row shows, once it asks for them', function () {
    $user = User::factory()->create();
    $older = Notification::factory()->for($user, 'notifiable')->read()->create(['created_at' => now()->subDay()]);
    $newer = Notification::factory()->for($user, 'notifiable')->create(['created_at' => now()->subHour()]);
    Notification::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/index')
            ->missing('latestNotifications')
            ->reloadOnly('latestNotifications', fn (Assert $reload) => $reload
                ->has('latestNotifications', 2)
                ->where('latestNotifications.0.id', $newer->id)
                ->where('latestNotifications.0.is_unread', true)
                ->where('latestNotifications.0.can.mark_read', true)
                ->where('latestNotifications.1.id', $older->id)
                ->where('latestNotifications.1.is_unread', false)
                ->where('latestNotifications.1.can.mark_read', false),
            ),
        );
});

test('a user without notifications gets an empty list, so the dashboard says so instead of showing made-up rows', function () {
    $user = User::factory()->create();
    Notification::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->reloadOnly('latestNotifications', fn (Assert $reload) => $reload
                ->has('latestNotifications', 0),
            ),
        );
});

test('the pages the dashboard links to open for the signed-in user', function (string $routeName) {
    $this->actingAs(User::factory()->create())
        ->get(route($routeName))
        ->assertOk();
})->with(['notifications.index', 'profile.edit', 'appearance.edit']);

test('the security link asks the user to confirm their password first', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('security.edit'))
        ->assertRedirect(route('password.confirm'));
});
