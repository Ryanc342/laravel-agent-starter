<?php

/*
| Proof that the test run reached its end. phpunit.xml runs the Structure suite
| last (Unit, Feature, Structure), and composer check records a pass
| (scripts/hooks/finish-check.php checked) only when this file was written after
| the check started: a run that stopped early or ran only some tests leaves it old.
*/

test('the test run reached its end', function () {
    $folder = dirname(__DIR__, 2).'/storage/framework/testing';

    if (! is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    expect(file_put_contents("{$folder}/ezdev-tests-finished", (string) microtime(true)))->toBeInt();
});
