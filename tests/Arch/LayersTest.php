<?php

/*
| Principle 2 in docs/golden-principles.md: layers only depend downwards.
| Each test name says what to do instead, because Pest prints it when a rule breaks.
|
| Every class in app/ follows these rules, the starter kit's auth and settings
| code included. Never add ->ignoring() to make a check pass; change the code.
*/

$http = [
    'App\Http',
    'Inertia',
    'Illuminate\Http',
    'Symfony\Component\HttpFoundation',
    'Illuminate\Support\Facades\Request',
    'Illuminate\Support\Facades\Response',
];

$gate = [
    'Illuminate\Support\Facades\Gate',
    'Illuminate\Contracts\Auth\Access\Gate',
    'Illuminate\Auth\Access\AuthorizationException',
];

arch('enums are the bottom layer: they use no models, policies, actions, http or integrations')
    ->expect('App\Enums')
    ->not->toUse(['App\Models', 'App\Policies', 'App\Actions', 'App\Http', 'App\Integrations']);

arch('models use no policies, actions, http or integrations: move that logic into an Action')
    ->expect('App\Models')
    ->not->toUse(['App\Policies', 'App\Actions', 'App\Http', 'App\Integrations']);

arch('policies only ask models and enums: they use no actions, http or integrations')
    ->expect('App\Policies')
    ->not->toUse(['App\Actions', 'App\Http', 'App\Integrations']);

arch('actions do not depend on http: take plain values, the controller handles the request')
    ->expect('App\Actions')
    ->not->toUse($http);

arch('actions do not ask for permission again: authorize once in the controller')
    ->expect('App\Actions')
    ->not->toUse($gate);

arch('notifications only describe a message: they use no actions, http or integrations, an Action sends them')
    ->expect('App\Notifications')
    ->not->toUse(['App\Actions', 'App\Http', 'App\Integrations']);

arch('resources only read: can.* asks the Policy, never an Action')
    ->expect('App\Http\Resources')
    ->not->toUse(['App\Actions', 'App\Integrations']);

arch('form requests check input shape only: business rules go in an Action')
    ->expect('App\Http\Requests')
    ->not->toUse(['App\Actions', 'App\Integrations']);

arch('controllers call no integrations and no DB facade: put the work in an Action')
    ->expect('App\Http\Controllers')
    ->not->toUse(['App\Integrations', 'Illuminate\Support\Facades\DB']);

arch('integrations are called only from actions')
    ->expect('App\Integrations')
    ->toOnlyBeUsedIn(['App\Actions', 'App\Integrations']);

arch('the http client is used only in app/Integrations: wrap the external service there')
    ->expect('Illuminate\Support\Facades\Http')
    ->toOnlyBeUsedIn('App\Integrations');
