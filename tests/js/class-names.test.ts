import { readdirSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import { expect, test } from 'vite-plus/test';

const root = join(import.meta.dirname, '../../resources/js');
const generated = ['actions', 'routes', 'wayfinder'];

function sourceFiles(directory: string): string[] {
    return readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
        const path = join(directory, entry.name);

        if (entry.isDirectory()) {
            return directory === root && generated.includes(entry.name)
                ? []
                : sourceFiles(path);
        }

        return entry.name.endsWith('.tsx') ? [path] : [];
    });
}

// A variant prefix such as `hover:` or `md:` that follows a letter, digit or
// bracket directly, with no space or colon between: two classes glued together
// (`hover:bg-destructive/90focus-visible:ring-...`), which Tailwind drops.
const variants =
    'hover|focus-visible|dark|md|lg|peer-data|group-data|aria|disabled';
const glued = new RegExp(`(?<=[a-z0-9\\])%/])(?:${variants}):`, 'g');

test('no class string has two classes glued together without a space', () => {
    const found: string[] = [];

    for (const file of sourceFiles(root)) {
        const text = readFileSync(file, 'utf8');

        for (const literal of text.matchAll(
            /"([^"\n]*)"|'([^'\n]*)'|`([^`]*)`/g,
        )) {
            const value = literal[1] ?? literal[2] ?? literal[3] ?? '';

            for (const token of value.split(/\s+/)) {
                if (glued.test(token)) {
                    found.push(
                        `${file.replace(root, 'resources/js')}: ${token}`,
                    );
                }

                glued.lastIndex = 0;
            }
        }
    }

    expect(found).toEqual([]);
});
