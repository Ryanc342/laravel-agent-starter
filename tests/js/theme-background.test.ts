import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { expect, test } from 'vite-plus/test';

const root = join(import.meta.dirname, '../..');
const css = readFileSync(join(root, 'resources/css/app.css'), 'utf8');
const view = readFileSync(join(root, 'resources/views/app.blade.php'), 'utf8');

function tokenIn(selector: ':root' | '.dark'): string {
    const block = css.slice(css.indexOf(`${selector} {`));
    const match = /--background:\s*([^;]+);/.exec(block);

    return match?.[1].trim() ?? '';
}

function paintedBefore(selector: 'html' | 'html.dark'): string {
    const escaped = selector.replace('.', '\\.');
    const match = new RegExp(
        `${escaped}\\s*\\{\\s*background-color:\\s*([^;]+);`,
    ).exec(view);

    return match?.[1].trim() ?? '';
}

// app.blade.php paints the page before the stylesheet has loaded, so it has to
// repeat the page background. The two copies must not drift apart: a stale one
// flashes another near-black in dark mode and on overscroll.
test('the colour painted before the stylesheet loads is the theme background', () => {
    expect(tokenIn(':root')).not.toBe('');
    expect(tokenIn('.dark')).not.toBe('');
    expect(paintedBefore('html')).toBe(tokenIn(':root'));
    expect(paintedBefore('html.dark')).toBe(tokenIn('.dark'));
});
