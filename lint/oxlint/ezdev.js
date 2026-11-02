/**
 * Ezdev rules for oxlint (docs/golden-principles.md, principles 4 and 7).
 * vite.config.ts loads this file under lint.jsPlugins; every rule is "ezdev/...".
 *
 * Only structure rules live here: what React may import and call. Whether
 * React decides a business rule (statuses, money, dates, permissions) needs
 * someone who reads the code, so those rules belong to the reviewer
 * (docs/prompts/review.md), not to a linter.
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

/**
 * The names along a member chain: page.props.auth.user.id gives
 * ["page", "props", "auth", "user", "id"]. Null when it is not a chain.
 */
function memberPath(node) {
    let current = unwrap(node);

    if (!current || current.type !== 'MemberExpression') {
        return null;
    }

    const names = [];

    while (current && current.type === 'MemberExpression') {
        const property = current.property;

        if (!current.computed) {
            names.unshift(property.name);
        } else if (
            property.type === 'Literal' &&
            typeof property.value === 'string'
        ) {
            names.unshift(property.value);
        } else {
            names.unshift('[]');
        }

        current = unwrap(current.object);
    }

    if (current && current.type === 'Identifier') {
        names.unshift(current.name);
    } else {
        names.unshift('()');
    }

    return names;
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

const noDirectBackendCalls = rule(
    'React reaches the backend only through Inertia',
    (context) => {
        const message =
            'React reaches the backend only through Inertia: no fetch or axios. Use the Wayfinder route from @/routes or @/actions with router or useForm. See docs/rules/frontend.md.';

        return {
            CallExpression(node) {
                const callee = unwrap(node.callee);
                const names = memberPath(callee);

                if (
                    (callee.type === 'Identifier' && callee.name === 'fetch') ||
                    names?.join('.') === 'window.fetch'
                ) {
                    context.report({ node, message });
                }
            },
            ImportDeclaration(node) {
                if (isAxios(node.source.value)) {
                    context.report({ node, message });
                }
            },
            ImportExpression(node) {
                if (isAxios(unwrap(node.source)?.value)) {
                    context.report({ node, message });
                }
            },
            MemberExpression(node) {
                const object = unwrap(node.object);
                const isGlobalAxios =
                    (object.type === 'Identifier' && object.name === 'axios') ||
                    memberPath(node)?.join('.') === 'window.axios';

                if (isGlobalAxios) {
                    context.report({ node, message });
                }
            },
        };
    },
);

function isAxios(source) {
    return (
        typeof source === 'string' &&
        (source === 'axios' || source.startsWith('axios/'))
    );
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

export default {
    meta: { name: 'ezdev' },
    rules: {
        'no-direct-backend-calls': noDirectBackendCalls,
        'component-boundaries': componentBoundaries,
    },
};
