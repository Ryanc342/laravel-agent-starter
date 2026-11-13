import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { expect, test } from 'vite-plus/test';

const css = readFileSync(
    join(import.meta.dirname, '../../resources/css/app.css'),
    'utf8',
);

// Components set `cursor-default` on menu items, so the rule sits outside
// @layer: an unlayered rule beats every utility class.
function unlayered(): string {
    return css.replace(/@layer base \{[\s\S]*?\n\}\n/, '');
}

test('every enabled clickable element gets the pointing hand, outside any layer', () => {
    const rule = /([^{}]*)\{\s*cursor:\s*pointer;\s*\}/.exec(unlayered());
    const selectors = rule?.[1] ?? '';

    for (const clickable of [
        'button:not(:disabled)',
        'a[href]',
        "[role='tab']",
        "[role='menuitem']",
        "[role='option']",
        "[role='checkbox']",
        'summary',
    ]) {
        expect(selectors).toContain(clickable);
    }
});

test('a disabled control keeps the normal arrow', () => {
    const rule = /([^{}]*)\{\s*cursor:\s*default;\s*\}/.exec(unlayered());
    const selectors = rule?.[1] ?? '';

    expect(selectors).toContain(':disabled');
    expect(selectors).toContain("[aria-disabled='true']");
    expect(selectors).toContain('[data-disabled]');
});
