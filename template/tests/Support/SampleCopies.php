<?php

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
        $copied = @file_get_contents("{$folder}/{$copy}");
        $live = @file_get_contents("{$root}/resources/js/{$page}");

        if ($copied === false) {
            $found[] = "docs/reference/pages/{$copy}: missing";
        } elseif ($copied !== $live) {
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
