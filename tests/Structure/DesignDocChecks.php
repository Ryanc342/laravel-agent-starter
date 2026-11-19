<?php

namespace Tests\Structure;

/**
 * The design spec (DESIGN.md, docs/design/; docs/golden-principles.md,
 * principle 10): its index of pattern files, the files the patterns name, and
 * that it names tokens and sizes instead of values. Each method returns what
 * it found wrong; DesignDocsTest.php expects none, and
 * template/tests/Lint/SkeletonChecksTest.php plants a broken spec for each.
 */
final class DesignDocChecks
{
    /**
     * A pixel, rem or em value, an arbitrary Tailwind value (`w-[13px]`), or a
     * Tailwind text, size, padding, margin or gap class.
     */
    private const string VALUE = '/\b\d+(?:\.\d+)?(?:px|rem|em)\b|\b[a-z-]+-\[[^\]]+\]|(?<![\w-])(?:text-(?:xs|sm|base|lg|xl|\dxl|display(?:-lg)?)|(?:min-|max-)?(?:h|w|size)-\d|(?:p|px|py|pt|pb|pl|pr|m|mx|my|gap|space-x|space-y)-\d)\b/';

    /**
     * Every pattern file is in the DESIGN.md index and has a Sample or Demo section.
     *
     * @return list<string>
     */
    public static function patternIndex(): array
    {
        $index = (string) @file_get_contents(ProjectFiles::root().'/DESIGN.md');
        $found = [];

        foreach (self::patterns() as $file) {
            $name = basename($file);

            if (! str_contains($index, "docs/design/patterns/{$name}")) {
                $found[] = "{$name}: not in the DESIGN.md index";
            }

            if (preg_match('/^## (Sample and demo|Sample|Demo)$/m', (string) file_get_contents($file)) !== 1) {
                $found[] = "{$name}: no Sample or Demo section";
            }
        }

        return $found;
    }

    /**
     * Every file a pattern file names (a sample, a demo) exists.
     *
     * @return list<string>
     */
    public static function patternFiles(): array
    {
        $found = [];

        foreach (self::patterns() as $file) {
            preg_match_all('#`((?:resources|docs|app)/[\w./-]*\.(?:tsx?|php))`#', (string) file_get_contents($file), $matches);

            foreach (array_unique($matches[1]) as $path) {
                if (! file_exists(ProjectFiles::root()."/{$path}")) {
                    $found[] = basename($file).": {$path}";
                }
            }
        }

        return $found;
    }

    /**
     * DESIGN.md and docs/design/ hold no pixel value and no Tailwind size class.
     *
     * @return list<string>
     */
    public static function designValues(): array
    {
        $root = ProjectFiles::root();
        $files = array_merge(
            is_file("{$root}/DESIGN.md") ? ["{$root}/DESIGN.md"] : [],
            glob("{$root}/docs/design/*.md") ?: [],
            self::patterns(),
        );
        $found = [];

        foreach ($files as $file) {
            foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $number => $line) {
                if (preg_match(self::VALUE, $line, $match) === 1) {
                    $found[] = basename($file).':'.($number + 1).': '.$match[0];
                }
            }
        }

        return $found;
    }

    /**
     * @return list<string>
     */
    private static function patterns(): array
    {
        return glob(ProjectFiles::root().'/docs/design/patterns/*.md') ?: [];
    }
}
