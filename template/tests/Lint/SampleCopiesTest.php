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
            @mkdir(dirname("{$root}/{$path}"), 0777, true);
            file_put_contents("{$root}/{$path}", "export default function Page() {}\n");
        }
    }

    expect(sampleCopyDifferences($root))->toBe([]);

    file_put_contents("{$root}/resources/js/pages/auth/login.tsx", "export default function Login() {}\n");
    unlink("{$root}/docs/reference/pages/welcome/index.tsx");
    unlink("{$root}/resources/js/pages/errors/show.tsx");
    file_put_contents("{$root}/docs/reference/pages/auth/extra.tsx", "export {};\n");

    expect(sampleCopyDifferences($root))->toEqualCanonicalizing([
        'docs/reference/pages/auth/login.tsx: differs from resources/js/pages/auth/login.tsx',
        'docs/reference/pages/welcome/index.tsx: missing',
        'resources/js/pages/errors/show.tsx: missing, the page docs/reference/pages/errors/show.tsx copies',
        'docs/reference/pages/auth/extra.tsx: not a copy of a page',
    ]);
});
