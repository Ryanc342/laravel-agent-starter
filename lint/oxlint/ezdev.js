/**
 * Ezdev's rules for oxlint. vite.config.ts loads this file under
 * lint.jsPlugins:
 *
 * - "ezdev/component-boundaries" (docs/golden-principles.md, principle 7):
 *   domain components never import another domain's components,
 *   components/shared never imports domain components, and nothing imports a
 *   page.
 * - "ezdev/no-module-level-t" (principle 10, docs/rules/translations.md): t()
 *   and tChoice() from @/lib/i18n never run at import time, when the language
 *   is not set yet.
 *
 * Only these rules and import/no-cycle are machine rules; the other
 * frontend rules are [review] rules (docs/prompts/review.md).
 *
 * Each message says what to do instead, because oxlint prints it.
 */
import path from 'node:path';

/**
 * The file's path from the project root ("resources/js/pages/x.tsx"), or null
 * when the file is not hand-written React code.
 */
function reactPath(context) {
    const file = `/${context.filename ?? ''}`.split(path.sep).join('/');
    const at = file.lastIndexOf('/resources/js/');
    const relative = at === -1 ? null : file.slice(at + 1);

    if (relative === null || !/\.(ts|tsx)$/.test(relative)) {
        return null;
    }

    return relative.endsWith('.d.ts') ? null : relative;
}

/**
 * Skips wrappers that do not change the value: a?.b, x!, x as T, (x).
 */
function unwrap(node) {
    let current = node;

    while (
        current &&
        [
            'ChainExpression',
            'TSNonNullExpression',
            'TSAsExpression',
            'TSSatisfiesExpression',
            'ParenthesizedExpression',
        ].includes(current.type)
    ) {
        current = current.expression;
    }

    return current;
}

function rule(description, create) {
    return {
        meta: { type: 'problem', docs: { description } },
        create(context) {
            const file = reactPath(context);

            return file === null ? {} : create(context, file);
        },
    };
}

/**
 * Where an import points inside resources/js, or null for a package.
 */
function importTarget(file, source) {
    if (source.startsWith('@/')) {
        return `resources/js/${source.slice(2)}`;
    }

    if (source.startsWith('./') || source.startsWith('../')) {
        return path.posix.normalize(
            path.posix.join(path.posix.dirname(file), source),
        );
    }

    return null;
}

const componentBoundaries = rule(
    'Components and pages keep their boundaries',
    (context, file) => {
        const domainOf = (where) =>
            where.match(/^resources\/js\/components\/([^/]+)\//)?.[1] ?? null;
        const from = domainOf(file);

        const check = (node, source) => {
            if (typeof source !== 'string') {
                return;
            }

            const target = importTarget(file, source);

            if (target === null) {
                return;
            }

            if (target.startsWith('resources/js/pages/')) {
                context.report({
                    node,
                    message:
                        'Pages are entry points; nothing imports a page. Move the shared part into resources/js/components/.',
                });

                return;
            }

            const to = domainOf(target);

            if (
                from === null ||
                to === null ||
                to === from ||
                to === 'ui' ||
                to === 'shared'
            ) {
                return;
            }

            context.report({
                node,
                message:
                    from === 'ui' || from === 'shared'
                        ? 'components/ui and components/shared are used by every domain, so they do not import a domain component. Pass data in as props instead.'
                        : `components/${from} does not import another domain's components (components/${to}). Move the shared part to resources/js/components/shared/.`,
            });
        };

        return {
            ImportDeclaration(node) {
                check(node, node.source.value);
            },
            ExportNamedDeclaration(node) {
                check(node, node.source?.value);
            },
            ExportAllDeclaration(node) {
                check(node, node.source.value);
            },
            ImportExpression(node) {
                check(node, unwrap(node.source)?.value);
            },
        };
    },
);

const FUNCTIONS = [
    'FunctionDeclaration',
    'FunctionExpression',
    'ArrowFunctionExpression',
];

const noModuleLevelT = rule(
    'Translate where a sentence is shown, never at import time',
    (context, file) => {
        const translators = new Set();

        return {
            ImportDeclaration(node) {
                if (
                    importTarget(file, node.source.value) !==
                    'resources/js/lib/i18n'
                ) {
                    return;
                }

                for (const specifier of node.specifiers) {
                    if (
                        specifier.type === 'ImportSpecifier' &&
                        ['t', 'tChoice'].includes(specifier.imported.name)
                    ) {
                        translators.add(specifier.local.name);
                    }
                }
            },
            CallExpression(node) {
                const callee = unwrap(node.callee);

                if (
                    callee?.type !== 'Identifier' ||
                    !translators.has(callee.name)
                ) {
                    return;
                }

                for (let at = node.parent; at; at = at.parent) {
                    if (FUNCTIONS.includes(at.type)) {
                        return;
                    }
                }

                context.report({
                    node,
                    message: `${callee.name}() runs at import time, before the language is set, so it keeps the English sentence. Mark the sentence with msg('...') and call t() where it is shown (docs/rules/translations.md).`,
                });
            },
        };
    },
);

export default {
    meta: { name: 'ezdev' },
    rules: {
        'component-boundaries': componentBoundaries,
        'no-module-level-t': noModuleLevelT,
    },
};
