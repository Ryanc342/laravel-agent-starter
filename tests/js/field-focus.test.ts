import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { expect, test } from 'vite-plus/test';

const root = join(import.meta.dirname, '../..');
const css = readFileSync(join(root, 'resources/css/app.css'), 'utf8');

const light = /:root \{([\s\S]*?)\n\}/.exec(css)?.[1] ?? '';
const dark = /\.dark \{([\s\S]*?)\n\}/.exec(css)?.[1] ?? '';

test('input-focus is a dark grey defined once for light and once for dark, and mapped to a Tailwind colour', () => {
    expect(light).toContain('--input-focus: oklch(0.5 0.025 265);');
    expect(dark).toContain('--input-focus: oklch(0.72 0.02 265);');
    expect(css).toContain('--color-input-focus: var(--input-focus);');
});
