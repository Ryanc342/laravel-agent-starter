<?php

use App\Models\Notification;
use App\Models\User;
use App\Notifications\AccountCreated;
use App\Notifications\EmailChanged;
use App\Notifications\PasswordChanged;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('a validation message and its field name read in Chinese', function () {
    $this->withUnencryptedCookie('locale', 'zh_CN')
        ->post(route('register.store'), [])
        ->assertSessionHasErrors([
            'name' => '姓名不能为空。',
            'email' => '邮箱不能为空。',
        ]);
});

test('the same validation reads in English with no cookie', function () {
    $this->post(route('register.store'), [])
        ->assertSessionHasErrors(['name' => 'The name field is required.']);
});

test('a notification stores its English sentence and its values', function () {
    $user = User::factory()->create();

    $user->notify(new EmailChanged('new@example.com'));
    $user->notify(new AccountCreated);
    $user->notify(new PasswordChanged);

    $stored = $user->notifications()->get()->mapWithKeys(fn (Notification $notification): array => [
        $notification->type => $notification->data,
    ]);

    expect($stored[EmailChanged::class])->toBe([
        'message' => 'Your email address was changed to :email.',
        'values' => ['email' => 'new@example.com'],
    ])->and($stored[AccountCreated::class]['message'])->toBe('Welcome to :app. Your account is ready.')
        ->and($stored[AccountCreated::class]['values'])->toBe(['app' => config('app.name')])
        ->and($stored[PasswordChanged::class]['values'])->toBe([]);
});

test('a notification is read in the language of the reader, not of the sender', function () {
    $user = User::factory()->create();
    $user->notify(new EmailChanged('new@example.com'));
    $notification = $user->notifications()->sole();

    app()->setLocale('en');
    expect($notification->message())->toBe('Your email address was changed to new@example.com.');

    app()->setLocale('zh_CN');
    expect($notification->message())->toBe('你的邮箱已更改为 new@example.com。');
});

test('a notification saved before this change, with a finished sentence, still reads as it was', function () {
    $user = User::factory()->create();
    $notification = Notification::factory()->for($user, 'notifiable')->create([
        'data' => ['message' => 'Your password was changed.'],
    ]);

    app()->setLocale('zh_CN');

    expect($notification->message())->toBe('Your password was changed.');
});

test('the notification page sends the message and the time in the reader\'s language', function () {
    $user = User::factory()->create();
    $user->notify(new PasswordChanged);
    $user->notifications()->update(['created_at' => Carbon::now()->subMinutes(5)]);

    $this->actingAs($user)
        ->withUnencryptedCookie('locale', 'zh_CN')
        ->get(route('notifications.index'))
        ->assertInertia(fn ($page) => $page
            ->where('notifications.data.0.message', '你的密码已更改。如果不是你本人操作，请立即重置密码。')
            ->where('notifications.data.0.created_at_diff', '5 分钟前'));
});

test('the verify-email and reset-password mails are in Chinese', function () {
    $user = User::factory()->unverified()->create();
    app()->setLocale('zh_CN');

    $verify = (new VerifyEmail)->toMail($user)->render()->toHtml();
    $reset = (new ResetPassword('token'))->toMail($user)->render()->toHtml();

    expect($verify)->toContain('验证邮箱地址')
        ->and($reset)->toContain('重置密码');
});
