import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { expect, test } from 'vite-plus/test';

const root = join(import.meta.dirname, '../..');
const css = readFileSync(join(root, 'resources/css/app.css'), 'utf8');
const read = (name: string) =>
    readFileSync(join(root, `resources/js/components/ui/${name}.tsx`), 'utf8');

const light = /:root \{([\s\S]*?)\n\}/.exec(css)?.[1] ?? '';
const dark = /\.dark \{([\s\S]*?)\n\}/.exec(css)?.[1] ?? '';

const fields = ['input', 'select', 'checkbox', 'input-otp'];

test('input-focus is a dark grey defined once for light and once for dark, and mapped to a Tailwind colour', () => {
    expect(light).toContain('--input-focus: oklch(0.5 0.025 265);');
    expect(dark).toContain('--input-focus: oklch(0.72 0.02 265);');
    expect(css).toContain('--color-input-focus: var(--input-focus);');
});

test('no field in components/ui has a focus ring or an error ring', () => {
    for (const name of fields) {
        const source = read(name);

        expect(source, name).not.toMatch(/ring-\[3px\]/);
        expect(source, name).not.toMatch(/aria-invalid:(dark:)?ring/);
        expect(source, name).not.toMatch(/focus-visible:ring/);
        expect(source, name).not.toMatch(/\bring-(1|ring)/);
    }
});

test('a focused field turns its border input-focus, and an error keeps the destructive border', () => {
    for (const name of ['input', 'select', 'checkbox']) {
        const source = read(name);

        expect(source, name).toContain('focus-visible:border-input-focus');
        expect(source, name).toContain('aria-invalid:border-destructive');
        expect(source, name).toContain(
            'aria-invalid:focus-visible:border-destructive',
        );
    }

    expect(read('input-otp')).toContain('border-input-focus');
});

test('buttons keep their keyboard ring', () => {
    expect(read('button')).toContain('focus-visible:ring-');
});
