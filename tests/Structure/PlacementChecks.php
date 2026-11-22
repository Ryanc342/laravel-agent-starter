<?php

namespace Tests\Structure;

/**
 * Where code may live (.claude/rules/skeleton.md, the locations, ALL-7 and
 * ALL-8): the folders of app/, where pages and components live, and code lives
 * only where the checks read it. Each method returns what it found wrong, as
 * "path: problem" lines; FoldersTest.php and CodeTest.php expect none, and
 * template/tests/Lint/SkeletonChecksTest.php plants a broken file for each.
 */
final class PlacementChecks
{
    /**
     * The starter kit's own components. A new component goes in a domain
     * folder or components/shared, never next to these.
     */
    private const array STARTER_KIT_COMPONENTS = [
        'app-content.tsx',
        'app-logo-icon.tsx',
        'app-logo.tsx',
        'app-shell.tsx',
        'app-sidebar-header.tsx',
        'app-sidebar.tsx',
        'breadcrumbs.tsx',
        'heading.tsx',
        'input-error.tsx',
        'nav-main.tsx',
        'nav-user.tsx',
        'text-link.tsx',
        'user-info.tsx',
        'user-menu-content.tsx',
    ];

    private const array GENERATED_JS = ['resources/js/actions', 'resources/js/routes', 'resources/js/wayfinder'];

    /** The operation part: the only files directly in app/Actions. */
    private const array OPERATION_PART = ['After.php', 'Operation.php', 'OperationSteps.php', 'OutsideCall.php', 'UserlessOperation.php'];

    /**
     * Every class in app/Actions/<Domain>/ is an operation: it extends
     * App\Actions\Operation or App\Actions\UserlessOperation. Read from the
     * source, so a planted fixture is checked the same way.
     *
     * @return list<string>
     */
    public static function actionsAreOperations(): array
    {
        $found = [];

        foreach (ProjectFiles::code(['app/Actions']) as $path) {
            $relative = substr($path, strlen('app/Actions/'));

            if (! str_contains($relative, '/')) {
                if (! in_array($relative, self::OPERATION_PART, true)) {
                    $found[] = "{$path}: only the operation part sits directly in app/Actions; an operation goes in app/Actions/<Domain>/";
                }

                continue;
            }

            $parent = self::parentClass(ProjectFiles::read($path));

            if (! in_array($parent, ['App\\Actions\\Operation', 'App\\Actions\\UserlessOperation'], true)) {
                $found[] = "{$path}: extends App\\Actions\\Operation or App\\Actions\\UserlessOperation";
            }
        }

        return $found;
    }

    /**
     * The fully qualified name of the class a PHP file's class extends, or null.
     */
    private static function parentClass(string $code): ?string
    {
        if (preg_match('/^\s*(?:(?:final|abstract|readonly)\s+)*class\s+\w+\s+extends\s+(\\\\?[\w\\\\]+)/m', $code, $extends) !== 1) {
            return null;
        }

        $name = $extends[1];

        if (str_starts_with($name, '\\')) {
            return substr($name, 1);
        }

        $first = explode('\\', $name)[0];

        if (preg_match('/^use\s+([\w\\\\]*\\\\'.preg_quote($first, '/').')\s*;/m', $code, $use) === 1) {
            return $use[1].substr($name, strlen($first));
        }

        preg_match('/^namespace\s+([\w\\\\]+)\s*;/m', $code, $namespace);

        return isset($namespace[1]) ? "{$namespace[1]}\\{$name}" : $name;
    }

    /**
     * @return list<string>
     */
    public static function appFolders(): array
    {
        $allowed = ['Actions', 'Concerns', 'Console', 'Enums', 'Http', 'Jobs', 'Models', 'Notifications', 'Policies', 'Providers', 'Support'];
        $found = [];

        foreach (self::entries('app') as $name) {
            if (! in_array($name, $allowed, true) || ! is_dir(ProjectFiles::root()."/app/{$name}")) {
                $found[] = "app/{$name}";
            }
        }

        return $found;
    }

    /**
     * @return list<string>
     */
    public static function pagesInDomainFolders(): array
    {
        return array_values(array_filter(
            ProjectFiles::in(['resources/js/pages']),
            fn (string $path): bool => dirname($path) === 'resources/js/pages',
        ));
    }

    /**
     * @return list<string>
     */
    public static function componentsRoot(): array
    {
        return array_values(array_filter(
            ProjectFiles::in(['resources/js/components']),
            fn (string $path): bool => dirname($path) === 'resources/js/components' && ! in_array(basename($path), self::STARTER_KIT_COMPONENTS, true),
        ));
    }

    /**
     * components/ui is shadcn's code: it imports no other component, page,
     * action, route or Wayfinder file of the app.
     *
     * @return list<string>
     */
    public static function uiImportsNoDomainCode(): array
    {
        $found = [];

        foreach (ProjectFiles::code(['resources/js/components/ui']) as $path) {
            foreach (self::importedPaths(ProjectFiles::read($path)) as $imported) {
                $target = self::importTarget($path, $imported) ?? '';

                if ((str_starts_with($target, 'resources/js/components/') && ! str_starts_with($target, 'resources/js/components/ui/'))
                    || preg_match('#^resources/js/(pages|actions|routes|wayfinder)(/|$)#', $target) === 1) {
                    $found[] = "{$path}: imports {$imported}";
                }
            }
        }

        return $found;
    }

    /**
     * The style page (/style) is the one design reference: every component in
     * components/shared is imported by one of its files (components/style or
     * pages/style), so it is shown there.
     *
     * @return list<string>
     */
    public static function styleShowsSharedComponents(): array
    {
        $shown = [];

        foreach (ProjectFiles::code(['resources/js/components/style', 'resources/js/pages/style']) as $path) {
            foreach (self::importedPaths(ProjectFiles::read($path)) as $imported) {
                $shown[] = self::importTarget($path, $imported);
            }
        }

        $found = [];

        foreach (ProjectFiles::code(['resources/js/components/shared']) as $path) {
            if (! in_array(preg_replace('/\.tsx?$/', '', $path), $shown, true)) {
                $found[] = "{$path}: not on the style page; show it in resources/js/components/style";
            }
        }

        return $found;
    }

    /**
     * @return list<string>
     */
    public static function typeScriptOnly(): array
    {
        return array_values(array_filter(
            ProjectFiles::in(['resources/js'], self::GENERATED_JS),
            fn (string $path): bool => preg_match('/\.tsx?$/', $path) !== 1,
        ));
    }

    /**
     * @return list<string>
     */
    public static function resourcesJsFolders(): array
    {
        $allowed = ['app.tsx', 'components', 'hooks', 'layouts', 'lib', 'pages', 'types', 'actions', 'routes', 'wayfinder'];

        return array_map(
            fn (string $name): string => "resources/js/{$name}",
            array_values(array_diff(self::entries('resources/js'), $allowed)),
        );
    }

    /**
     * @return list<string>
     */
    public static function docsMarkdownOnly(): array
    {
        return array_values(array_filter(
            ProjectFiles::in(['docs']),
            fn (string $path): bool => ! str_ends_with($path, '.md'),
        ));
    }

    /**
     * @return list<string>
     */
    public static function publicNoPhp(): array
    {
        return array_values(array_filter(
            ProjectFiles::in(['public'], ['public/storage', 'public/build']),
            fn (string $path): bool => $path !== 'public/index.php' && preg_match('/\.(php\d?|phtml|phar)$/i', $path) === 1,
        ));
    }

    /**
     * @return list<string>
     */
    public static function noSymlinks(): array
    {
        $found = [];

        foreach (ProjectFiles::everywhere() as $path) {
            $file = ProjectFiles::root()."/{$path}";

            if (is_link($file)) {
                $found[] = "{$path} -> ".readlink($file);
            }
        }

        return $found;
    }

    /**
     * TypeScript imports only from inside resources/js (tests/js also from
     * tests/js): no relative path that leaves it and no path into docs/.
     *
     * @return list<string>
     */
    public static function importsStayInside(): array
    {
        $found = [];

        foreach (ProjectFiles::code(['resources/js', 'tests/js'], self::GENERATED_JS) as $path) {
            $inside = str_starts_with($path, 'tests/js/') ? '#^(resources|tests)/js/#' : '#^resources/js/#';

            foreach (self::importedPaths(ProjectFiles::read($path)) as $imported) {
                $target = self::importTarget($path, $imported);

                if (preg_match('#(^|/)docs(/|$)#', $imported) === 1 || ($target !== null && preg_match($inside, $target) !== 1)) {
                    $found[] = "{$path}: imports {$imported}";
                }
            }
        }

        return $found;
    }

    /**
     * PHP requires and includes only .php files: code in another kind of file
     * escapes PHPStan.
     *
     * @return list<string>
     */
    public static function requiresOnlyPhp(): array
    {
        $found = [];

        $files = array_filter(
            ProjectFiles::in(['app', 'bootstrap', 'config', 'database', 'routes', 'tests'], ['bootstrap/cache', 'bootstrap/ssr']),
            fn (string $path): bool => str_ends_with($path, '.php'),
        );

        foreach ($files as $path) {
            $tokens = token_get_all(ProjectFiles::read($path));

            foreach ($tokens as $index => $token) {
                if (! is_array($token) || ! in_array($token[0], [T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE], true)) {
                    continue;
                }

                // The path is the expression up to the end of the statement; its last string must end in .php.
                $expression = $token[1];
                $file = null;
                $depth = 0;

                foreach (array_slice($tokens, $index + 1) as $part) {
                    $text = is_array($part) ? $part[1] : $part;
                    $depth += match ($text) {
                        '(', '[' => 1,
                        ')', ']' => -1,
                        default => 0,
                    };

                    if ($depth < 0 || ($depth === 0 && in_array($text, [';', ','], true)) || (is_array($part) && $part[0] === T_CLOSE_TAG)) {
                        break;
                    }

                    if (is_array($part) && in_array($part[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)) {
                        $file = trim($part[1], '\'"');
                    }

                    $expression .= $text;
                }

                if ($file === null || ! str_ends_with($file, '.php')) {
                    $found[] = "{$path}:{$token[2]}: ".preg_replace('/\s+/', ' ', $expression);
                }
            }
        }

        return $found;
    }

    /**
     * @return list<string>
     */
    public static function bladeNoPhp(): array
    {
        $found = [];

        foreach (ProjectFiles::in(['resources/views']) as $path) {
            foreach (explode("\n", ProjectFiles::read($path)) as $number => $line) {
                if (preg_match('/(?<![\w@])@php\b|<\?(php|=)/i', $line, $match) === 1) {
                    $found[] = "{$path}:".($number + 1).": {$match[0]}";
                }
            }
        }

        return $found;
    }

    /**
     * The names directly inside a folder, or none when the folder is missing.
     *
     * @return list<string>
     */
    private static function entries(string $folder): array
    {
        return is_dir(ProjectFiles::root()."/{$folder}") ? ProjectFiles::entries($folder) : [];
    }

    /**
     * The module paths a TypeScript file imports: import … from, export … from,
     * import '…', import('…'), require('…') and import.meta.glob('…').
     *
     * @return list<string>
     */
    private static function importedPaths(string $code): array
    {
        preg_match_all('/\bfrom\s*[\'"]([^\'"]+)[\'"]|\bimport\s*\(?\s*[\'"]([^\'"]+)[\'"]|\brequire\s*\(\s*[\'"]([^\'"]+)[\'"]/', $code, $matches);
        $paths = array_merge($matches[1], $matches[2], $matches[3]);

        preg_match_all('/\bimport\.meta\.glob(?:Eager)?\s*(?:<[^>]*>)?\s*\(\s*(\[[^\]]*\]|[\'"][^\'"]+[\'"])/', $code, $globs);

        foreach ($globs[1] as $glob) {
            preg_match_all('/[\'"]!?([^\'"]+)[\'"]/', $glob, $patterns);
            $paths = array_merge($paths, $patterns[1]);
        }

        return array_values(array_filter($paths, fn (string $path): bool => $path !== ''));
    }

    /**
     * Where an imported path points, relative to the project root, or null for
     * a package. "@/" is resources/js/, as in tsconfig.json.
     */
    private static function importTarget(string $importer, string $path): ?string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        if (str_starts_with($path, '@/')) {
            $path = 'resources/js/'.substr($path, 2);
        } elseif (str_starts_with($path, '.')) {
            $path = dirname($importer).'/'.$path;
        } else {
            return null;
        }

        // Resolve "." and "..": a path that climbs above the project root keeps its "..".
        $parts = [];

        foreach (explode('/', $path) as $part) {
            if ($part === '..' && $parts !== [] && end($parts) !== '..') {
                array_pop($parts);
            } elseif ($part !== '.' && $part !== '') {
                $parts[] = $part;
            }
        }

        return implode('/', $parts);
    }
}
