<?php

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/*
| Consistency test. The "Mark as read" button must be shown exactly when
| pressing it succeeds. The button and the endpoint ask the same owners (the
| Policy ability and Notification::canBeMarkedRead()), so this test fails as
| soon as one of them writes its own copy of a condition.
*/

dataset('who and which notification', [
    'own unread notification' => [true, false],
    'own read notification' => [true, true],
    'another user\'s unread notification' => [false, false],
    'another user\'s read notification' => [false, true],
]);

/**
 * Creates the case, returns the user and the notification.
 *
 * @return array{0: User, 1: Notification}
 */
function notificationCase(bool $own, bool $read): array
{
    $user = User::factory()->create();
    $factory = $read ? Notification::factory()->read() : Notification::factory();
    $notification = $own ? $factory->for($user, 'notifiable')->create() : $factory->create();

    return [$user, $notification];
}

/**
 * What the Resource tells React for this user.
 *
 * @return array<string, bool>
 */
function notificationButtonsFor(User $user, Notification $notification): array
{
    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);

    return NotificationResource::make($notification)->resolve($request)['can'];
}

test('the mark as read button is shown exactly when marking as read succeeds', function (bool $own, bool $read) {
    [$user, $notification] = notificationCase($own, $read);

    $shown = notificationButtonsFor($user, $notification)['mark_read'];
    $response = $this->actingAs($user)->post(route('notifications.read.store', $notification));

    expect($shown)->toBe($response->isRedirect() && ! session()->has('errors'));
})->with('who and which notification');

dataset('whose notifications', [
    'no notifications' => [false, false, false],
    'only read notifications' => [false, true, false],
    'an unread notification' => [true, false, false],
    'an unread and a read notification' => [true, true, false],
    'only another user\'s unread notification' => [false, false, true],
]);

test('the mark all as read button is shown exactly when pressing it succeeds', function (bool $ownUnread, bool $ownRead, bool $otherUnread) {
    $user = User::factory()->create();

    if ($ownUnread) {
        Notification::factory()->for($user, 'notifiable')->create();
    }

    if ($ownRead) {
        Notification::factory()->read()->for($user, 'notifiable')->create();
    }

    if ($otherUnread) {
        Notification::factory()->create();
    }

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertInertia(fn (Assert $page) => $page->where('can.mark_all_read', $ownUnread));

    $response = $this->post(route('notifications.read-all.store'));

    $ownUnread
        ? $response->assertSessionHasNoErrors()
        : $response->assertSessionHasErrors('notifications');

    expect(Notification::query()->ownedBy($user)->unread()->count())->toBe(0);
})->with('whose notifications');
