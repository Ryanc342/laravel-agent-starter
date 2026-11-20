<?php

require_once dirname(__DIR__, 3).'/tests/Support/Workspace.php';

/*
| The sample copies (docs/reference/pages/): copies of the template's own pages
| that the pattern files of docs/design/patterns/ tell the AI to copy. In a
| project the live pages are ordinary pages it may change or delete; in the
| template each copy equals its page, so the samples stay what the app runs.
*/

/** The copies, by their path under docs/reference/pages/ => the page under resources/js/. */
const SAMPLE_COPIES = [
    'auth/login.tsx' => 'pages/auth/login.tsx',
    'auth/register.tsx' => 'pages/auth/register.tsx',
    'dashboard/index.tsx' => 'pages/dashboard/index.tsx',
    'errors/show.tsx' => 'pages/errors/show.tsx',
    'notifications/index.tsx' => 'pages/notifications/index.tsx',
    'welcome/index.tsx' => 'pages/welcome/index.tsx',
    'settings/appearance.tsx' => 'pages/settings/appearance.tsx',
    'settings/profile.tsx' => 'pages/settings/profile.tsx',
    'settings/security.tsx' => 'pages/settings/security.tsx',
    'components/notifications/notification-row.tsx' => 'components/notifications/notification-row.tsx',
];

/**
 * What is wrong with the copies in the project at $root: a copy that differs
 * from its page, a missing copy, and a file there that is no copy.
 *
 * @return list<string>
 */
function sampleCopyDifferences(string $root): array
{
    $found = [];
    $folder = "{$root}/docs/reference/pages";

    foreach (SAMPLE_COPIES as $copy => $page) {
        if (! is_file("{$folder}/{$copy}")) {
            $found[] = "docs/reference/pages/{$copy}: missing";
        } elseif (! is_file("{$root}/resources/js/{$page}")) {
            $found[] = "resources/js/{$page}: missing, the page docs/reference/pages/{$copy} copies";
        } elseif (sampleRead("{$folder}/{$copy}") !== sampleRead("{$root}/resources/js/{$page}")) {
            $found[] = "docs/reference/pages/{$copy}: differs from resources/js/{$page}";
        }
    }

    $files = is_dir($folder) ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS)) : [];

    foreach ($files as $file) {
        $path = substr($file->getPathname(), strlen($folder) + 1);

        if (! isset(SAMPLE_COPIES[$path])) {
            $found[] = "docs/reference/pages/{$path}: not a copy of a page";
        }
    }

    return $found;
}

/** A file's text; a file that cannot be read fails the check, never compares as equal. */
function sampleRead(string $path): string
{
    $text = file_get_contents($path);

    return $text === false ? throw new RuntimeException("cannot read {$path}") : $text;
}

test('every sample copy equals the template\'s own page', function () {
    expect(sampleCopyDifferences(dirname(__DIR__, 3)))->toBe([]);
});

test('the copy check fails when a copy differs from its page, is missing, or is no copy', function () {
    $root = workspaceFolder('samples');

    foreach (SAMPLE_COPIES as $copy => $page) {
        foreach (["docs/reference/pages/{$copy}", "resources/js/{$page}"] as $path) {
            workspaceWrite($root, $path, "export default function Page() {}\n");
        }
    }

    expect(sampleCopyDifferences($root))->toBe([]);

    workspaceWrite($root, 'resources/js/pages/auth/login.tsx', "export default function Login() {}\n");
    workspaceRemove("{$root}/docs/reference/pages/welcome/index.tsx");
    workspaceRemove("{$root}/resources/js/pages/errors/show.tsx");
    workspaceWrite($root, 'docs/reference/pages/auth/extra.tsx', "export {};\n");

    expect(sampleCopyDifferences($root))->toEqualCanonicalizing([
        'docs/reference/pages/auth/login.tsx: differs from resources/js/pages/auth/login.tsx',
        'docs/reference/pages/welcome/index.tsx: missing',
        'resources/js/pages/errors/show.tsx: missing, the page docs/reference/pages/errors/show.tsx copies',
        'docs/reference/pages/auth/extra.tsx: not a copy of a page',
    ]);
});
