<?php

/*
| Architecture rules (ARCHITECTURE.md). Each test name says what to do instead,
| because Pest prints the name when a rule breaks.
|
| Starter kit scaffolding keeps its own shape and is ignored here. Do not add new
| entries to this list to make a check pass; change the code instead.
*/

$scaffolding = [
    'App\Actions\Fortify',
    'App\Http\Controllers\Settings',
    'App\Console\Commands\InstallFeaturesCommand',
];

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

arch('actions are final classes')
    ->expect('App\Actions')
    ->classes()
    ->toBeFinal()
    ->ignoring($scaffolding);

arch('actions expose a handle() method')
    ->expect('App\Actions')
    ->toHaveMethod('handle')
    ->ignoring($scaffolding);

arch('actions have no public methods besides the constructor and handle(): one operation per Action')
    ->expect('App\Actions')
    ->not->toHavePublicMethodsBesides(['__construct', 'handle'])
    ->ignoring($scaffolding);

arch('actions do not depend on http: take plain values, the controller handles the request')
    ->expect('App\Actions')
    ->not->toUse($http)
    ->ignoring($scaffolding);

arch('actions do not ask for permission again: authorize once in the controller')
    ->expect('App\Actions')
    ->not->toUse($gate)
    ->ignoring($scaffolding);

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

arch('no service classes: write an Action instead')
    ->expect('App')
    ->not->toHaveSuffix('Service');

arch('no repositories: query through model scopes instead')
    ->expect('App')
    ->not->toHaveSuffix('Repository');
