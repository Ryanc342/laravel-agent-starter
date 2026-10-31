<?php

/*
|--------------------------------------------------------------------------
| Test case
|--------------------------------------------------------------------------
|
| Feature tests written as Pest functions run on Tests\TestCase, so they can
| use the application (routes, database, actingAs). The starter kit's own
| class-based tests keep working unchanged. Arch and Structure tests need no
| application.
|
*/

pest()->extend(Tests\TestCase::class)->in('Feature');
