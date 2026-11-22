<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
| One tour of every page, in the four ways people see it: on a computer and on
| a phone, light and dark. Each page loads with no JavaScript error and no
| console message, never scrolls sideways, and has no accessibility issue at
| any level, colour contrast included. On a Mac each page also matches its
| screenshot baseline, the one the user approved (PAGE-8): the browser draws
| text a little differently on each system, so GitHub's Linux runs every other
| check of the tour but not this one. The pages show the same data on every
| run: a fixed day (React never reads the clock) and a fixed user.
*/

beforeEach(function () {
    $this->travelTo('2026-11-20 10:00:00');
});

dataset('screens', [
    'computer, light' => [false, false],
    'computer, dark' => [false, true],
    'phone, light' => [true, false],
    'phone, dark' => [true, true],
]);

/**
 * Opens a page the way one screen shows it and checks what every page keeps.
 */
function tourPage(string $url, bool $phone, bool $dark): void
{
    checkPage(openPage($url, $phone, $dark));
}

/**
 * A page as one screen shows it: a computer or a phone, light or dark.
 */
function openPage(string $url, bool $phone, bool $dark): mixed
{
    $page = visit($url);

    if ($phone) {
        $page = $page->on()->mobile();
    }

    return $dark ? $page->inDarkMode() : $page;
}

/**
 * What every page keeps.
 */
function checkPage(mixed $page): void
{
    $page->assertNoSmoke()
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth', true)
        ->assertNoAccessibilityIssues(3);

    if (PHP_OS_FAMILY === 'Darwin') {
        $page->assertScreenshotMatches();
    }
}

/**
 * The same user on every run, so the pages that show the user look the same.
 */
function tourUser(bool $verified = true): User
{
    $factory = $verified ? User::factory() : User::factory()->unverified();

    return $factory->create(['name' => 'Mei Tan', 'email' => 'mei@example.com']);
}

test('the public pages and the sign-in pages', function (string $url, bool $phone, bool $dark) {
    tourPage($url, $phone, $dark);
})->with([
    'home' => '/',
    'log in' => '/login',
    'register' => '/register',
    'forgot password' => '/forgot-password',
    'reset password' => '/reset-password/token?email=mei@example.com',
    'not found' => '/no-such-page',
])->with('screens');

test('the pages of a signed-in user', function (string $url, bool $phone, bool $dark) {
    $this->actingAs(tourUser());

    tourPage($url, $phone, $dark);
})->with([
    'dashboard' => '/dashboard',
    'notifications' => '/notifications',
    'profile' => '/settings/profile',
    'appearance' => '/settings/appearance',
    'confirm password' => '/user/confirm-password',
])->with('screens');

test('the security page, once the password is confirmed', function (bool $phone, bool $dark) {
    $this->actingAs(tourUser());

    // The page asks for the password first (password.confirm); the factory's password is "password".
    $page = openPage('/settings/security', $phone, $dark)
        ->type('password', 'password')
        ->press('Confirm')
        ->assertPathIs('/settings/security');

    checkPage($page);
})->with('screens');

test('the page of a user whose email is not verified', function (bool $phone, bool $dark) {
    $this->actingAs(tourUser(verified: false));

    tourPage('/email/verify', $phone, $dark);
})->with('screens');

test('the style overview, where every shared component is shown', function (bool $phone, bool $dark) {
    app()->detectEnvironment(fn () => 'local');

    tourPage('/style', $phone, $dark);
})->with('screens');
