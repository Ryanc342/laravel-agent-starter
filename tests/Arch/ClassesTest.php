<?php

/*
| Principles 3, 5, 6 and 7 in docs/golden-principles.md: every class does only
| its own job, input is checked at the edge, no extra layers, names follow the
| conventions. Each test name says what to do instead, because Pest prints it
| when a rule breaks.
*/

arch('actions are final classes')
    ->expect('App\Actions')
    ->classes()
    ->toBeFinal();

arch('actions expose a handle() method')
    ->expect('App\Actions')
    ->toHaveMethod('handle');

arch('actions have no public methods besides the constructor and handle(): one operation per Action')
    ->expect('App\Actions')
    ->not->toHavePublicMethodsBesides(['__construct', 'handle']);

arch('controllers have only the standard methods: give any other operation its own controller, like NotificationReadController')
    ->expect('App\Http\Controllers')
    ->not->toHavePublicMethodsBesides(['__construct', '__invoke', 'index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

arch('form requests have no authorize(): the controller asks the Policy with Gate::authorize()')
    ->expect('App\Http\Requests')
    ->not->toHaveMethod('authorize');

arch('app/Enums holds enums only: put other code in the layer it belongs to')
    ->expect('App\Enums')
    ->toBeEnums();

arch('no interfaces: use the concrete class unless the user asks for an interface')
    ->expect('App')
    ->not->toBeInterface();

arch('no service classes: write an Action instead')
    ->expect('App')
    ->not->toHaveSuffix('Service');

arch('no repositories: query through model scopes instead')
    ->expect('App')
    ->not->toHaveSuffix('Repository');

arch('no DTO classes: pass validated arrays, models and enums')
    ->expect('App')
    ->not->toHaveSuffix('DTO');

arch('no Dto classes: pass validated arrays, models and enums')
    ->expect('App')
    ->not->toHaveSuffix('Dto');

arch('controllers are named *Controller')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('form requests are named *Request')
    ->expect('App\Http\Requests')
    ->toHaveSuffix('Request');

arch('resources are named *Resource')
    ->expect('App\Http\Resources')
    ->toHaveSuffix('Resource');

arch('policies are named *Policy')
    ->expect('App\Policies')
    ->toHaveSuffix('Policy');
