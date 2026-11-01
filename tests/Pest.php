<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test case
|--------------------------------------------------------------------------
|
| Feature tests written as Pest functions run on Tests\TestCase, so they can
| use the application (routes, database, actingAs). The starter kit's own
| class-based tests keep working unchanged. Structure tests need no
| application.
|
*/

pest()->extend(TestCase::class)->in('Feature');
