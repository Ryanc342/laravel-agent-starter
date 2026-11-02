<?php

namespace Tests\Structure;

use FilesystemIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Lists the project's own files for the structure tests, as paths relative to
 * the project root ("app/Models/User.php"), sorted.
 *
 * Folders named .git, vendor or node_modules are never entered, at any depth,
 * nor a folder that is a checkout of its own (it has a .git). Symlinks are
 * listed but never followed. Placeholder files (.gitkeep, .DS_Store) are left
 * out: they keep a folder in git and hold nothing.
 */
final class ProjectFiles
{
    private const array SKIPPED_FOLDERS = ['.git', 'vendor', 'node_modules'];

    private const array PLACEHOLDERS = ['.gitkeep', '.DS_Store'];

    /**
     * Folders the framework writes at run time. With the folders .gitignore
     * names from the root, they are not the repository's files.
     */
    private const array GENERATED = ['storage', 'bootstrap/cache', 'bootstrap/ssr', 'public/build', 'public/hot', 'public/storage'];

    public static function root(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function read(string $path): string
    {
        $file = self::root().'/'.$path;

        return is_file($file) ? (string) file_get_contents($file) : '';
    }

    /**
     * Every file under the given folders, recursively.
     *
     * @param  list<string>  $folders  relative to the root
     * @param  list<string>  $skip  files or folders to leave out, relative to the root
     * @return list<string>
     */
    public static function in(array $folders, array $skip = []): array
    {
        $root = self::root();
        $files = [];

        foreach ($folders as $folder) {
            $start = in_array($folder, ['', '.'], true) ? $root : $root.'/'.trim($folder, '/');

            if (! is_dir($start) || is_link($start)) {
                continue;
            }

            $filter = function (SplFileInfo $file) use ($root, $skip): bool {
                $path = substr($file->getPathname(), strlen($root) + 1);

                if (self::skipped($path, $skip) || in_array($file->getFilename(), self::PLACEHOLDERS, true)) {
                    return false;
                }

                if ($file->isDir() && ! $file->isLink()) {
                    return ! in_array($file->getFilename(), self::SKIPPED_FOLDERS, true) && ! file_exists("{$file->getPathname()}/.git");
                }

                return true;
            };

            $iterator = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($start, FilesystemIterator::SKIP_DOTS),
                $filter,
            ));

            foreach ($iterator as $file) {
                $files[] = substr($file->getPathname(), strlen($root) + 1);
            }
        }

        sort($files);

        return array_values(array_unique($files));
    }

    /**
     * Every file of the repository: the whole project without the generated
     * folders and the folders .gitignore names from the root.
     *
     * @param  list<string>  $skip
     * @return list<string>
     */
    public static function everywhere(array $skip = []): array
    {
        $ignored = [];

        foreach (explode("\n", self::read('.gitignore')) as $line) {
            $line = trim($line);

            if (str_starts_with($line, '/') && strpbrk($line, '*?[!') === false) {
                $ignored[] = trim($line, '/');
            }
        }

        return self::in(['.'], array_merge(self::GENERATED, $ignored, $skip));
    }

    /**
     * The names directly inside a folder, files and folders.
     *
     * @return list<string>
     */
    public static function entries(string $folder): array
    {
        $names = array_diff(scandir(self::root().'/'.$folder) ?: [], ['.', '..'], self::PLACEHOLDERS);
        sort($names);

        return array_values($names);
    }

    /**
     * @param  list<string>  $skip
     */
    private static function skipped(string $path, array $skip): bool
    {
        foreach ($skip as $prefix) {
            $prefix = rtrim($prefix, '/');

            if ($path === $prefix || str_starts_with($path, "{$prefix}/")) {
                return true;
            }
        }

        return false;
    }
}
