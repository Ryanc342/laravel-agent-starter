<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('a visit with no cookie and no Accept-Language is in English', function () {
    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));

    expect(app()->getLocale())->toBe('en');
});

test('a Chinese browser gets Chinese on its first visit', function (string $header) {
    $this->withHeader('Accept-Language', $header)
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'zh_CN'));
})->with(['zh', 'zh-CN,zh;q=0.9', 'zh-TW,zh;q=0.9,en;q=0.8']);

test('the browser\'s first choice decides, by q value and then by order', function (string $header, string $expected) {
    $this->withHeader('Accept-Language', $header)
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', $expected));
})->with([
    'english listed first' => ['en-US,zh;q=0.5', 'en'],
    'chinese first' => ['zh-TW,en;q=0.5', 'zh_CN'],
    'q reorders the written order' => ['en;q=0.4,zh-HK;q=0.9', 'zh_CN'],
    'a wildcard is skipped' => ['*,zh;q=0.5', 'zh_CN'],
    'a language that only starts with zh' => ['zha', 'en'],
]);

test('another language falls back to English', function () {
    $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
});

test('the cookie beats the browser', function () {
    $this->withHeader('Accept-Language', 'zh-CN')
        ->withUnencryptedCookie('locale', 'en')
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));

    $this->withHeader('Accept-Language', 'en-US')
        ->withUnencryptedCookie('locale', 'zh_CN')
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'zh_CN'));
});

test('the signed-in user\'s choice beats the cookie', function () {
    $user = User::factory()->create(['locale' => 'zh_CN']);

    $this->actingAs($user)
        ->withUnencryptedCookie('locale', 'en')
        ->withHeader('Accept-Language', 'en-US')
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'zh_CN'));
});

test('a signed-in user with no choice follows the cookie, then the browser', function () {
    $user = User::factory()->create(['locale' => null]);

    $this->actingAs($user)
        ->withUnencryptedCookie('locale', 'zh_CN')
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'zh_CN'));

    $this->actingAs($user)
        ->withHeader('Accept-Language', 'zh-CN')
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'zh_CN'));
});

test('an unknown cookie value is ignored', function () {
    $this->withUnencryptedCookie('locale', 'klingon')
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
});

test('the page\'s html lang follows the language', function () {
    $this->withUnencryptedCookie('locale', 'zh_CN')
        ->get(route('home'))
        ->assertSee('<html lang="zh-CN"', false);
});

test('the page\'s html lang is en in English', function () {
    $this->get(route('home'))->assertSee('<html lang="en"', false);
});

test('Chinese pages send the Chinese sentences to React', function () {
    $this->withUnencryptedCookie('locale', 'zh_CN')
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('translations.Save changes', '保存更改')
            ->etc());
});

test('English pages send no sentences: a sentence is its own English text', function () {
    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('translations', []));
});
