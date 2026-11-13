import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { expect, test } from 'vite-plus/test';

const css = readFileSync(
    join(import.meta.dirname, '../../resources/css/app.css'),
    'utf8',
);

// foundations.md: larger headings come only from this token pair, one size for
// a phone and one for a computer.
test('the display size is a token pair, larger on a computer than on a phone', () => {
    const phone = /--text-display:\s*([\d.]+)rem/.exec(css);
    const computer = /--text-display-lg:\s*([\d.]+)rem/.exec(css);

    expect(phone).not.toBeNull();
    expect(computer).not.toBeNull();
    expect(Number(computer?.[1])).toBeGreaterThan(Number(phone?.[1]));
});
