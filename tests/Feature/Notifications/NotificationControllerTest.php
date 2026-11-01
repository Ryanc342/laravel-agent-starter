<?php

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));

    $notification = Notification::factory()->create();
    $this->post(route('notifications.read.store', $notification))->assertRedirect(route('login'));
    $this->post(route('notifications.read-all.store'))->assertRedirect(route('login'));
});

test('the list shows only the user\'s own notifications, newest first', function () {
    $user = User::factory()->create();
    Notification::factory()->for($user, 'notifiable')->create(['created_at' => now()->subDay()]);
    $newest = Notification::factory()->for($user, 'notifiable')->create();
    Notification::factory()->create();

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notifications/index')
            ->has('notifications.data', 2)
            ->where('notifications.data.0.id', $newest->id)
            ->where('unreadNotifications', 2));
});

test('the list shows 20 notifications per page', function () {
    $user = User::factory()->create();
    Notification::factory()->for($user, 'notifiable')->count(21)->create();

    $this->actingAs($user)
        ->get(route('notifications.index', ['page' => 2]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('notifications.data', 1)
            ->where('notifications.meta.current_page', 2)
            ->where('notifications.meta.total', 21));
});

test('a user can mark their own notification as read', function () {
    $user = User::factory()->create();
    $notification = Notification::factory()->for($user, 'notifiable')->create();

    $this->actingAs($user)
        ->post(route('notifications.read.store', $notification))
        ->assertRedirect(route('notifications.index'));

    expect($notification->refresh()->read_at)->not->toBeNull();
});

test('a user cannot mark another user\'s notification as read', function () {
    $user = User::factory()->create();
    $notification = Notification::factory()->create();

    $this->actingAs($user)
        ->post(route('notifications.read.store', $notification))
        ->assertForbidden();

    expect($notification->refresh()->read_at)->toBeNull();
});

test('a user can mark all of their notifications as read', function () {
    $user = User::factory()->create();
    Notification::factory()->for($user, 'notifiable')->count(2)->create();

    $this->actingAs($user)
        ->post(route('notifications.read-all.store'))
        ->assertRedirect(route('notifications.index'));

    expect(Notification::query()->ownedBy($user)->unread()->count())->toBe(0);
});
