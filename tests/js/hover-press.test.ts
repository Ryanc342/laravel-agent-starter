import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { expect, test } from 'vite-plus/test';

const read = (name: string) =>
    readFileSync(
        join(
            import.meta.dirname,
            `../../resources/js/components/ui/${name}.tsx`,
        ),
        'utf8',
    );

// Principle 10: every enabled clickable element has a grey hover and a press
// state one step darker. press-states.test.ts covers Button; these are the
// other clickable components.

test('the Select trigger turns grey on hover and one step darker while pressed', () => {
    const source = read('select');
    const trigger = source.slice(
        source.indexOf('function SelectTrigger'),
        source.indexOf('function SelectContent'),
    );

    expect(trigger).toContain('hover:bg-accent');
    expect(trigger).toContain('active:bg-accent-pressed');
});

test('the Checkbox has a hover and a press state, ticked or not', () => {
    const checkbox = read('checkbox');

    expect(checkbox).toContain('hover:bg-accent');
    expect(checkbox).toContain('active:bg-accent-pressed');
    expect(checkbox).toContain(
        'data-[state=checked]:active:bg-primary-pressed',
    );
});

test('a Badge used as a link has a press state in every variant', () => {
    const variants =
        /variant: \{([\s\S]*?)\n {6}\},/.exec(read('badge'))?.[1] ?? '';
    const entries = variants
        .split(/\n {8}(?=\w+:)/)
        .map((entry) => entry.trim())
        .filter(Boolean);

    expect(entries.length).toBe(6);

    for (const entry of entries) {
        expect(entry).toMatch(/\[a&\]:active:bg-[a-z]+-pressed/);
    }
});

test('the navigation menu has a hover and a press state and shows the current item in the press colour', () => {
    const menu = read('navigation-menu');

    expect(menu).toContain('hover:bg-accent');
    expect(menu).toContain('active:bg-accent-pressed');
    expect(menu).toContain('data-[active=true]:bg-accent-pressed');
});
