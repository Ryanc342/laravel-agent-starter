<?php

use Illuminate\Foundation\DevCommands;

test('composer dev runs the schedule next to the server and the queue', function () {
    $commands = array_column(DevCommands::commands(), 'command', 'name');

    expect($commands['scheduler'] ?? null)->toBe('php artisan schedule:work')
        ->and($commands)->toHaveKeys(['server', 'queue']);
});
