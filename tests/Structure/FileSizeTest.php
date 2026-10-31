<?php

/*
| Small files are easier for AI to read and change correctly. Generated files,
| the shadcn UI components and two large pages that came with the starter kit
| are skipped. Do not add entries to $skip to make the check pass; split the file.
*/

test('source files stay under 250 lines: split big files into components, Actions or concerns', function () {
    $root = dirname(__DIR__, 2);
    $maxLines = 250;

    $skip = [
        'resources/js/actions/',
        'resources/js/routes/',
        'resources/js/wayfinder/',
        'resources/js/components/ui/',
        'resources/js/pages/welcome.tsx',
        'resources/js/components/two-factor-setup-modal.tsx',
    ];

    $tooLong = [];

    foreach (['app', 'database', 'routes', 'resources/js'] as $directory) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator("{$root}/{$directory}", FilesystemIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            $path = substr($file->getPathname(), strlen($root) + 1);

            if (! preg_match('/\.(php|ts|tsx)$/', $path)) {
                continue;
            }

            foreach ($skip as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    continue 2;
                }
            }

            $lines = count(file($file->getPathname()));

            if ($lines > $maxLines) {
                $tooLong[] = "{$path} ({$lines} lines)";
            }
        }
    }

    expect($tooLong)->toBe([]);
});
