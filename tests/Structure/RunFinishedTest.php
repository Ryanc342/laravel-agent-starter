<?php

/*
| Proof that the test run reached its end. phpunit.xml runs the Structure suite
| last (Unit, Feature, Structure), and composer check records a pass
| (scripts/hooks/finish-check.php checked) only when this file belongs to the
| same run and was written after it started. Other test invocations cannot
| provide this check's proof, even when they finish later.
*/

test('the test run reached its end', function () {
    $folder = dirname(__DIR__, 2).'/storage/framework/testing';

    if (! is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $proof = json_encode(['id' => getenv('EZDEV_CHECK_RUN_ID') ?: null, 'time' => microtime(true)], JSON_THROW_ON_ERROR);

    expect(file_put_contents("{$folder}/ezdev-tests-finished", $proof))->toBeInt();
});
