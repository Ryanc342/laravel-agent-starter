<?php

use Symfony\Component\Process\Process;

require_once __DIR__.'/../Support/SampleCopies.php';

test('every sample copy equals the template\'s own page', function () {
    expect(sampleCopyDifferences(dirname(__DIR__, 3)))->toBe([]);
});

test('the copy check fails when a copy differs from its page, is missing, or is no copy', function () {
    $root = sys_get_temp_dir().'/ezdev-samples-'.bin2hex(random_bytes(6));
    register_shutdown_function(fn () => (new Process(['rm', '-rf', $root]))->run());

    foreach (SAMPLE_COPIES as $copy => $page) {
        foreach (["docs/reference/pages/{$copy}", "resources/js/{$page}"] as $path) {
            sampleWrite($root, $path, "export default function Page() {}\n");
        }
    }

    expect(sampleCopyDifferences($root))->toBe([]);

    sampleWrite($root, 'resources/js/pages/auth/login.tsx', "export default function Login() {}\n");
    sampleRemove("{$root}/docs/reference/pages/welcome/index.tsx");
    sampleRemove("{$root}/resources/js/pages/errors/show.tsx");
    sampleWrite($root, 'docs/reference/pages/auth/extra.tsx', "export {};\n");

    expect(sampleCopyDifferences($root))->toEqualCanonicalizing([
        'docs/reference/pages/auth/login.tsx: differs from resources/js/pages/auth/login.tsx',
        'docs/reference/pages/welcome/index.tsx: missing',
        'resources/js/pages/errors/show.tsx: missing, the page docs/reference/pages/errors/show.tsx copies',
        'docs/reference/pages/auth/extra.tsx: not a copy of a page',
    ]);
});
