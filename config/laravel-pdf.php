<?php

/*
| Only what this project changes from spatie/laravel-pdf's own config
| (vendor/spatie/laravel-pdf/config/laravel-pdf.php), which supplies the rest.
|
| DOMPDF's built-in fonts have no Chinese characters, so the PDF views load
| Noto Sans SC from resources/fonts through @font-face (docs/design/patterns/print.md).
| `chroot` is the folder DOMPDF may read font files from, `font_dir` is where it
| keeps the fonts it installs and `font_cache` where it caches their metrics:
| storage/fonts, which is writable and kept out of git. One render with the
| full fonts peaks at about 130 MB, so a server that makes PDFs needs a PHP
| memory_limit of at least 256M.
*/

return [
    'dompdf' => [
        'is_remote_enabled' => env('LARAVEL_PDF_DOMPDF_REMOTE_ENABLED', false),
        'chroot' => resource_path('fonts'),
        'font_dir' => storage_path('fonts'),
        'font_cache' => storage_path('fonts'),
    ],
];
