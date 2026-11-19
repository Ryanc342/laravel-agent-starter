import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import babel from '@rolldown/plugin-babel';
import tailwindcss from '@tailwindcss/vite';
import react, { reactCompilerPreset } from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        !process.env.VITEST &&
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.tsx'],
                refresh: true,
                fonts: [
                    bunny('Instrument Sans', {
                        weights: [400, 500, 600],
                    }),
                ],
            }),
        inertia(),
        react(),
        babel({
            presets: [reactCompilerPreset()],
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ]),
    resolve: {
        tsconfigPaths: true,
    },
    server: {
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    // Frontend tests (vp test, part of composer check): .claude/rules/tests.md.
    test: {
        include: ['tests/js/**/*.test.{ts,tsx}'],
        environment: 'jsdom',
        setupFiles: ['tests/js/setup.ts'],
    },
    lint: {
        // The default plugins plus import (for import/no-cycle). Setting
        // plugins replaces the default list, so every plugin is named here.
        plugins: ['typescript', 'unicorn', 'oxc', 'import'],
        // Ezdev's own rules, docs/golden-principles.md principles 7 and 10.
        // Only these and import cycles are machine rules.
        jsPlugins: ['./lint/oxlint/ezdev.js'],
        rules: {
            'ezdev/component-boundaries': 'error',
            'ezdev/no-module-level-t': 'error',
            'import/no-cycle': 'error',
            // Only no-cycle is wanted from the import plugin; these two are
            // its rules that oxlint turns on by default.
            'import/default': 'off',
            'import/namespace': 'off',
        },
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'docs/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: [
            '.github/**',
            'composer.json',
            'docs/**',
            '*.md',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
    },
});
