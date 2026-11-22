<?php

namespace Tests\Feature\Parts;

use App\Http\Resources\ResourceData;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;

/*
| The record part (Route::record(), App\Http\Controllers\RecordController):
| find the record in the URL -> authorize view -> load the relations ->
| Resource -> page. The model, the Policy and the Resource below exist only
| for this test.
*/

uses(RefreshDatabase::class);

final class PartShownNotification extends Notification {}

final class PartShownPolicy
{
    public function view(User $user, Notification $notification): bool
    {
        return $notification->isOwnedBy($user);
    }
}

final class PartShownResource extends ResourceData
{
    public function __construct(
        public string $id,
        public string $message,
        public bool $owner_loaded,
        public ?string $viewer_id,
    ) {}

    public static function from(Model $record, ?User $viewer): static
    {
        assert($record instanceof Notification);

        return new self(
            id: $record->id,
            message: $record->message(),
            owner_loaded: $record->relationLoaded('notifiable'),
            viewer_id: $viewer?->id,
        );
    }
}

beforeEach(function () {
    Gate::policy(PartShownNotification::class, PartShownPolicy::class);

    Route::middleware(['web', 'auth'])->group(function () {
        Route::record('part-test/shown/{notification}', PartShownNotification::class, PartShownResource::class, 'notifications/index', 'item', ['notifiable']);
        Route::record('part-test/shown-plain/{notification}', PartShownNotification::class, PartShownResource::class, 'notifications/index', 'item');
        Route::record('part-test/broken-shown/{notification}', PartShownNotification::class, Notification::class, 'notifications/index', 'item');
    });
});

function shown(User $user, string $message): Notification
{
    return Notification::factory()->for($user, 'notifiable')->create([
        'data' => ['message' => $message, 'values' => []],
    ]);
}

test('the page gets the record as its Resource, built for the viewer, with the relations the route names', function () {
    $user = User::factory()->create();
    $notification = shown($user, 'Invoice paid');

    $this->actingAs($user)->get("/part-test/shown/{$notification->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notifications/index')
            ->where('item', [
                'id' => $notification->id,
                'message' => 'Invoice paid',
                'owner_loaded' => true,
                'viewer_id' => $user->id,
            ]));

    $this->actingAs($user)->get("/part-test/shown-plain/{$notification->id}")
        ->assertInertia(fn (Assert $page) => $page->where('item.owner_loaded', false));
});

test('a record the user may not view is refused, and a key that matches no record is a 404', function () {
    $notification = shown(User::factory()->create(), 'Someone else\'s');

    $this->actingAs(User::factory()->create())->get("/part-test/shown/{$notification->id}")->assertForbidden();
    $this->actingAs(User::factory()->create())->get('/part-test/shown/no-such-key')->assertNotFound();
});

test('the Resource a route names must be a Resource', function () {
    $user = User::factory()->create();
    $notification = shown($user, 'Invoice paid');
    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($user)->get("/part-test/broken-shown/{$notification->id}"))->toThrow(LogicException::class);
});
