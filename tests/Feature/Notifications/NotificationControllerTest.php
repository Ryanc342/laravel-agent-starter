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
            ->where('notificationSummary.unread', 2));
});

test('notifications created in the same second each appear on exactly one page', function () {
    $user = User::factory()->create();
    Notification::factory()->for($user, 'notifiable')->count(25)->create(['created_at' => now()]);

    $ids = collect([1, 2])->flatMap(fn (int $page) => collect($this->actingAs($user)
        ->get(route('notifications.index', ['page' => $page]))
        ->inertiaProps('notifications.data'))->pluck('id'));

    expect($ids)->toHaveCount(25)
        ->and($ids->unique())->toHaveCount(25);
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

test('the list pages by the rows per page in the URL', function (int $perPage) {
    $user = User::factory()->create();
    Notification::factory()->for($user, 'notifiable')->count(101)->create();

    $this->actingAs($user)
        ->get(route('notifications.index', ['per_page' => $perPage]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('notifications.data', $perPage)
            ->where('notifications.meta.per_page', $perPage)
            ->where('notifications.meta.total', 101));
})->with([10, 20, 50, 100]);

test('a rows-per-page value that is not offered pages by 20', function (mixed $value) {
    $user = User::factory()->create();
    Notification::factory()->for($user, 'notifiable')->count(25)->create();

    $this->actingAs($user)
        ->get(route('notifications.index', ['per_page' => $value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('notifications.data', 20)
            ->where('notifications.meta.per_page', 20));
})->with(['999', '0', '-5', 'abc', '50abc', [['10']]]);

test('without a rows-per-page value the list pages by 20', function () {
    $user = User::factory()->create();
    Notification::factory()->for($user, 'notifiable')->count(25)->create();

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertInertia(fn (Assert $page) => $page->has('notifications.data', 20));
});

test('the page links keep the rows per page', function () {
    $user = User::factory()->create();
    Notification::factory()->for($user, 'notifiable')->count(25)->create();

    $this->actingAs($user)
        ->get(route('notifications.index', ['per_page' => 10]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notifications.links.next', fn (string $url) => str_contains($url, 'per_page=10') && str_contains($url, 'page=2')));
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
