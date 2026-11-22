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
    // Frontend tests (vp test, part of composer check).
    test: {
        include: ['tests/js/**/*.test.{ts,tsx}'],
        environment: 'jsdom',
        setupFiles: ['tests/js/setup.ts'],
    },
    lint: {
        // The default plugins plus import (for import/no-cycle). Setting
        // plugins replaces the default list, so every plugin is named here.
        plugins: ['typescript', 'unicorn', 'oxc', 'import'],
        rules: {
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
            'resources/js/types/generated.d.ts',
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
            '.mcp.json',
            'boost.json',
            'composer.json',
            'docs/**',
            '*.md',
            'resources/js/components/ui/*',
            'resources/js/types/generated.d.ts',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
    },
});
