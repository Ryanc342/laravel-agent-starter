<?php

use Inertia\Testing\AssertableInertia as Assert;

test('the style overview page opens in the local environment, without signing in', function () {
    app()->detectEnvironment(fn () => 'local');

    $this->get(route('style'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('style/index'));
});

test('the style overview page is not there in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->get(route('style'))
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('errors/show')->where('status', 404));
});

test('the style overview page is not there in any other environment either', function (string $environment) {
    app()->detectEnvironment(fn () => $environment);

    $this->get(route('style'))->assertNotFound();
})->with(['testing', 'staging']);
