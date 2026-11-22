<?php

namespace Tests\Feature\Parts;

use App\Http\Resources\ExportsCsv;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\ResourceData;
use App\Models\Notification;
use App\Models\User;
use App\Policies\NotificationPolicy;
use Closure;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;

/*
| The query part's search, filters, sort and export (Route::listing(),
| App\Http\Requests\ListingRequest, App\Http\Controllers\ListingController).
| The model, the enum and the Resources below exist only for this test.
*/

uses(RefreshDatabase::class);

enum PartReadState: string
{
    case Read = 'read';
    case Unread = 'unread';
}

/** Notifications with a search and two filters. */
final class PartListedNotification extends Notification
{
    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $text): void
    {
        $query->where('data', 'like', '%'.$text.'%');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ofType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function readState(Builder $query, PartReadState $state): void
    {
        $state === PartReadState::Read ? $query->whereNotNull('read_at') : $query->whereNull('read_at');
    }
}

/** A notification as an exported list gets it. */
abstract class PartExportBase extends ResourceData implements ExportsCsv
{
    final public function __construct(
        public string $id,
        public string $message,
        public ?string $read_on,
        public bool $is_unread,
    ) {}

    public static function from(Model $record, ?User $viewer): static
    {
        assert($record instanceof Notification);

        return new static(
            id: $record->id,
            message: $record->message(),
            read_on: $record->read_at?->format('Y-m-d'),
            is_unread: $record->isUnread(),
        );
    }
}

final class PartExportedResource extends PartExportBase
{
    public static function csvColumns(): array
    {
        return ['Message' => 'message', 'Read on' => 'read_on'];
    }
}

final class PartFlagColumnResource extends PartExportBase
{
    public static function csvColumns(): array
    {
        return ['Unread' => 'is_unread'];
    }
}

final class PartMissingColumnResource extends PartExportBase
{
    public static function csvColumns(): array
    {
        return ['Owner' => 'owner'];
    }
}

beforeEach(function () {
    Gate::policy(PartListedNotification::class, NotificationPolicy::class);

    Route::middleware(['web', 'auth'])->group(function () {
        Route::listing('part-test/list', PartListedNotification::class, NotificationResource::class, 'notifications/index', 'items', ['listOrder'], [
            'search' => 'search',
            'filters' => ['type' => ['ofType', 'string'], 'state' => ['readState', PartReadState::class]],
            'sorts' => ['created_at'],
        ]);
        Route::listing('part-test/broken-list', PartListedNotification::class, NotificationResource::class, 'notifications/index', 'items', [], [
            'filters' => ['owner' => ['noSuchScope', 'string']],
        ]);
        Route::listing('part-test/exported-list', PartListedNotification::class, PartExportedResource::class, 'notifications/index', 'items', ['listOrder'], [
            'search' => 'search',
            'export' => true,
        ]);
        Route::listing('part-test/export-without-columns', PartListedNotification::class, NotificationResource::class, 'notifications/index', 'items', [], ['export' => true]);
        Route::listing('part-test/export-flag-column', PartListedNotification::class, PartFlagColumnResource::class, 'notifications/index', 'items', [], ['export' => true]);
        Route::listing('part-test/export-missing-column', PartListedNotification::class, PartMissingColumnResource::class, 'notifications/index', 'items', [], ['export' => true]);
    });
});

/**
 * @param  array<string, mixed>  $attributes
 */
function listed(User $user, string $message, array $attributes = []): Notification
{
    return Notification::factory()->for($user, 'notifiable')->create([
        'data' => ['message' => $message, 'values' => []],
        ...$attributes,
    ]);
}

test('search, filters and sort choose the rows, and the page gets the current choices', function () {
    $user = User::factory()->create();
    $paid = listed($user, 'Invoice paid', ['type' => 'type-a', 'created_at' => now()->subDays(3)]);
    $sent = listed($user, 'Invoice sent', ['type' => 'type-a', 'read_at' => now(), 'created_at' => now()->subDays(2)]);
    listed($user, 'Password changed', ['type' => 'type-a', 'created_at' => now()->subDay()]);
    listed($user, 'Invoice of another type', ['type' => 'type-b']);
    listed(User::factory()->create(), 'Invoice of someone else', ['type' => 'type-a']);

    $this->actingAs($user)->get('/part-test/list?search=Invoice&type=type-a&sort=created_at')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('items.data', 2)
            ->where('items.data.0.id', $paid->id)
            ->where('items.data.1.id', $sent->id)
            ->where('itemsQuery', ['search' => 'Invoice', 'filters' => ['type' => 'type-a'], 'sort' => 'created_at']));

    $this->actingAs($user)->get('/part-test/list?search=Invoice&type=type-a&sort=-created_at')
        ->assertInertia(fn (Assert $page) => $page->where('items.data.0.id', $sent->id));

    $this->actingAs($user)->get('/part-test/list?search=Invoice&type=type-a&state=unread')
        ->assertInertia(fn (Assert $page) => $page
            ->has('items.data', 1)
            ->where('items.data.0.id', $paid->id)
            ->where('itemsQuery.filters', ['type' => 'type-a', 'state' => 'unread']));
});

test('without choices the list shows everything the user may see, in its own order', function () {
    $user = User::factory()->create();
    listed($user, 'Older', ['created_at' => now()->subDay()]);
    $newest = listed($user, 'Newest');

    $this->actingAs($user)->get('/part-test/list')
        ->assertInertia(fn (Assert $page) => $page
            ->has('items.data', 2)
            ->where('items.data.0.id', $newest->id)
            ->where('itemsQuery', ['search' => null, 'filters' => [], 'sort' => null]));
});

test('a sort or a filter value the route does not allow is an input error', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/part-test/list?sort=type')->assertSessionHasErrors('sort');
    $this->actingAs($user)->get('/part-test/list?state=maybe')->assertSessionHasErrors('state');
});

test('a query value the route does not name is ignored', function () {
    $user = User::factory()->create();
    listed($user, 'One');

    $this->actingAs($user)->get('/part-test/list?owner=5')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('items.data', 1));
});

test('the next page keeps the search, filters and sort', function () {
    $user = User::factory()->create();

    foreach (range(1, 12) as $day) {
        listed($user, "Invoice {$day}", ['type' => 'type-a', 'created_at' => now()->subDays($day)]);
    }

    $this->actingAs($user)->get('/part-test/list?search=Invoice&type=type-a&sort=created_at&per_page=10')
        ->assertInertia(fn (Assert $page) => $page
            ->has('items.data', 10)
            ->where('items.links.next', fn (string $next): bool => str_contains($next, 'search=Invoice')
                && str_contains($next, 'type=type-a') && str_contains($next, 'sort=created_at') && str_contains($next, 'page=2')));
});

test('a scope the route names must exist on the model', function () {
    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs(User::factory()->create())->get('/part-test/broken-list'))->toThrow(LogicException::class);
});

test('export=csv sends every row the list shows, with its search, as a CSV file, and not one page', function () {
    $user = User::factory()->create();

    foreach (range(1, 12) as $day) {
        listed($user, "Invoice {$day}", ['created_at' => now()->subDays($day)]);
    }

    listed($user, 'Invoice read', ['read_at' => now()->setDate(2026, 11, 20)]);
    listed($user, 'Password changed', ['created_at' => now()->subDays(20)]);
    listed(User::factory()->create(), 'Invoice of someone else');

    $response = $this->actingAs($user)->get('/part-test/exported-list?search=Invoice&per_page=10&export=csv');

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->assertDownload('items-'.now()->format('Y-m-d').'.csv');

    $content = $response->streamedContent();
    $lines = explode("\n", trim($content));

    expect($content)->toStartWith("\u{FEFF}Message,\"Read on\"\n")
        ->and($lines)->toHaveCount(14)
        ->and($lines[1])->toBe('"Invoice read",2026-11-20')
        ->and($lines[2])->toBe('"Invoice 1",')
        ->and($lines[13])->toBe('"Invoice 12",');
});

/**
 * 502 notifications of the user, "Invoice 1" the newest: an export reads their contents in two batches.
 *
 * @return Collection<int, Notification>
 */
function manyListed(User $user): Collection
{
    return Notification::factory()->count(502)->for($user, 'notifiable')
        ->sequence(fn (Sequence $sequence): array => [
            'data' => ['message' => 'Invoice '.($sequence->index + 1), 'values' => []],
            'created_at' => now()->subMinutes($sequence->index + 1),
        ])->create();
}

/**
 * The first column of every row of an exported file, in order.
 *
 * @return list<string|null>
 */
function exportedMessages(string $content): array
{
    $lines = array_slice(explode("\n", trim(substr($content, strlen("\u{FEFF}")))), 1);

    return array_map(fn (string $line): ?string => str_getcsv($line, escape: '')[0], $lines);
}

/**
 * Runs $change once, while the export reads the contents of its first batch.
 */
function whileExporting(Closure $change): void
{
    $done = false;

    PartListedNotification::retrieved(function () use ($change, &$done): void {
        if (! $done) {
            $done = true;
            $change();
        }
    });
}

test('a record added while the file is sent shifts nothing: no row is written twice', function () {
    $user = User::factory()->create();
    manyListed($user);
    whileExporting(fn () => listed($user, 'Added later'));

    $messages = exportedMessages($this->actingAs($user)->get('/part-test/exported-list?export=csv')->streamedContent());

    expect($messages)->toHaveCount(502)
        ->and(array_unique($messages))->toHaveCount(502)
        ->and($messages)->not->toContain('Added later');
});

test('a written record removed while the file is sent shifts nothing: no row is left out', function () {
    $user = User::factory()->create();
    $first = manyListed($user)->first();
    whileExporting(fn () => $first?->delete());

    $messages = exportedMessages($this->actingAs($user)->get('/part-test/exported-list?export=csv')->streamedContent());

    expect($messages)->toHaveCount(502)
        ->and($messages[0])->toBe('Invoice 1')
        ->and(array_slice($messages, 500))->toBe(['Invoice 501', 'Invoice 502']);
});

test('a record removed before its batch is read is not written', function () {
    $user = User::factory()->create();
    $last = manyListed($user)->last();
    whileExporting(fn () => $last?->delete());

    $messages = exportedMessages($this->actingAs($user)->get('/part-test/exported-list?export=csv')->streamedContent());

    expect($messages)->toHaveCount(501)
        ->and($messages[500])->toBe('Invoice 501');
});

test('an exported cell that a spreadsheet would run as a formula stays text', function () {
    $user = User::factory()->create();
    listed($user, '=HYPERLINK("http://example.com")');

    $lines = explode("\n", trim($this->actingAs($user)->get('/part-test/exported-list?export=csv')->streamedContent()));

    expect($lines[1])->toBe('"\'=HYPERLINK(""http://example.com"")",');
});

test('export is an input error where the value is not csv, and ignored where the route does not allow it', function () {
    $user = User::factory()->create();
    listed($user, 'One');

    $this->actingAs($user)->get('/part-test/exported-list?export=xlsx')->assertSessionHasErrors('export');
    $this->actingAs($user)->get('/part-test/list?export=csv')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('items.data', 1));
});

test('a list that allows export needs a Resource with CSV columns, each a field that holds text, a number or null', function (string $uri) {
    $user = User::factory()->create();
    listed($user, 'One');
    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($user)->get($uri))->toThrow(LogicException::class);
})->with([
    'no CSV columns' => '/part-test/export-without-columns',
    'a column that holds a flag' => '/part-test/export-flag-column',
    'a column that is not a field' => '/part-test/export-missing-column',
]);
