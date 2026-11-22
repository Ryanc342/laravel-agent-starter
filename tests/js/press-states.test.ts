import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { expect, test } from 'vite-plus/test';

const root = join(import.meta.dirname, '../..');
const css = readFileSync(join(root, 'resources/css/app.css'), 'utf8');

// The light values sit in `:root`, the dark values in `.dark`.
const light = /:root \{([\s\S]*?)\n\}/.exec(css)?.[1] ?? '';
const dark = /\.dark \{([\s\S]*?)\n\}/.exec(css)?.[1] ?? '';

const pressed = [
    'primary-pressed',
    'secondary-pressed',
    'destructive-pressed',
    'accent-pressed',
    'sidebar-accent-pressed',
];

test('every press colour is defined once for light and once for dark, and mapped to a Tailwind colour', () => {
    for (const token of pressed) {
        expect(light).toContain(`--${token}:`);
        expect(dark).toContain(`--${token}:`);
        expect(css).toContain(`--color-${token}: var(--${token});`);
    }
});
