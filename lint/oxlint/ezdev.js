/**
 * Ezdev rules for oxlint (docs/golden-principles.md, principles 4 and 7).
 * vite.config.ts loads this file under lint.jsPlugins; every rule is "ezdev/...".
 *
 * The rules only look at the hand-written React code in resources/js. They
 * match by name and shape, so a match can be display logic after all. Then ask
 * the user to adjust this file; never rename things to slip past a rule.
 *
 * Each message says what to do instead, because oxlint prints it.
 */
import path from 'node:path';

const DISPLAY_FORMAT_FILE = 'resources/js/lib/format.ts';

const STATUS_WORDS = new Set([
    'status',
    'state',
    'role',
    'type',
    'kind',
    'stage',
    'level',
]);

// Browser objects (an event, a file, an HTTP response) and flash or toast
// messages have a type or status that is not a business value.
const BROWSER_HOLDERS = new Set([
    'event',
    'evt',
    'e',
    'file',
    'target',
    'input',
    'element',
    'el',
    'response',
    'res',
    'error',
    'err',
    'flash',
    'toast',
    'props',
]);

const MONEY_FIELD =
    /^(price|total|amount|subtotal|tax|discount|cost|balance|fee)/i;

const EQUALITY = new Set(['==', '===', '!=', '!==']);
const ORDER = new Set(['<', '<=', '>', '>=']);
const ARITHMETIC = new Set(['+', '-', '*', '/', '%']);
const ARITHMETIC_ASSIGN = new Set(['+=', '-=', '*=', '/=', '%=']);

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

function isNumber(node) {
    const value = unwrap(node);

    return value?.type === 'Literal' && typeof value.value === 'number';
}

function isText(node) {
    const value = unwrap(node);

    return (
        (value?.type === 'Literal' && typeof value.value === 'string') ||
        value?.type === 'TemplateLiteral'
    );
}

/**
 * The text a string or template starts with, or null. For "/" + slug it
 * follows the left side of the +.
 */
function leadingText(node) {
    let value = unwrap(node);

    while (value?.type === 'BinaryExpression' && value.operator === '+') {
        value = unwrap(value.left);
    }

    if (value?.type === 'Literal' && typeof value.value === 'string') {
        return value.value;
    }

    if (value?.type === 'TemplateLiteral') {
        return value.quasis[0]?.value.cooked ?? '';
    }

    return null;
}

/**
 * task.status, user.role and the like, but not event.type or flash.type.
 */
function isBusinessValue(node) {
    const names = memberPath(node);

    if (!names || names.length < 2) {
        return false;
    }

    const field = names[names.length - 1];
    const holder = names[names.length - 2];

    return STATUS_WORDS.has(field) && !BROWSER_HOLDERS.has(holder);
}

function isMoneyField(node) {
    const names = memberPath(node);

    if (!names || names.length < 2) {
        return false;
    }

    const field = names[names.length - 1];
    const holder = names[names.length - 2];

    return MONEY_FIELD.test(field) && holder !== 'meta';
}

function isDateValue(node) {
    const value = unwrap(node);

    if (value?.type === 'NewExpression') {
        return (
            value.callee.type === 'Identifier' && value.callee.name === 'Date'
        );
    }

    if (value?.type !== 'CallExpression') {
        return false;
    }

    const names = memberPath(value.callee);

    return (
        names !== null &&
        ((names.length === 2 && names[0] === 'Date' && names[1] === 'now') ||
            names[names.length - 1] === 'getTime')
    );
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

const noPermissionChecks = rule(
    'React does not check permissions',
    (context) => ({
        BinaryExpression(node) {
            if (!EQUALITY.has(node.operator)) {
                return;
            }

            const touchesUser = [node.left, node.right].some((side) => {
                const names = memberPath(side) ?? [];

                return names.some(
                    (name, i) => name === 'auth' && names[i + 1] === 'user',
                );
            });

            if (touchesUser) {
                context.report({
                    node,
                    message:
                        'React does not check permissions: show each button from the can.* flag the Resource sends. See docs/rules/frontend.md.',
                });
            }
        },
    }),
);

const noBusinessComparisons = rule(
    'React does not compare statuses, roles or types',
    (context) => {
        const message =
            'React does not compare statuses, roles or types: use the is_* flags and labels the Resource sends. See docs/rules/frontend.md.';

        return {
            BinaryExpression(node) {
                if (!EQUALITY.has(node.operator)) {
                    return;
                }

                const compared =
                    (isBusinessValue(node.left) && !isNumber(node.right)) ||
                    (isBusinessValue(node.right) && !isNumber(node.left));

                if (compared) {
                    context.report({ node, message });
                }
            },
            SwitchStatement(node) {
                if (isBusinessValue(node.discriminant)) {
                    context.report({ node: node.discriminant, message });
                }
            },
            CallExpression(node) {
                const names = memberPath(node.callee);

                if (
                    names?.[names.length - 1] === 'includes' &&
                    node.arguments.some(isBusinessValue)
                ) {
                    context.report({ node, message });
                }
            },
        };
    },
);

const noDateMath = rule(
    'React does not compare or subtract dates',
    (context, file) => {
        if (file === DISPLAY_FORMAT_FILE) {
            return {};
        }

        return {
            BinaryExpression(node) {
                if (!ORDER.has(node.operator) && node.operator !== '-') {
                    return;
                }

                if (isDateValue(node.left) || isDateValue(node.right)) {
                    context.report({
                        node,
                        message: `React does not compare or subtract dates: the Resource sends is_* flags and formatted dates. Display-only formatting goes in ${DISPLAY_FORMAT_FILE}. See docs/rules/frontend.md.`,
                    });
                }
            },
        };
    },
);

const noMoneyMath = rule(
    'React does not calculate money or sum lists',
    (context, file) => {
        if (file === DISPLAY_FORMAT_FILE) {
            return {};
        }

        const message = `React does not calculate money or sum lists: the Resource sends prices, totals and discounts. Display-only formatting goes in ${DISPLAY_FORMAT_FILE}. See docs/rules/frontend.md.`;

        return {
            BinaryExpression(node) {
                if (!ARITHMETIC.has(node.operator)) {
                    return;
                }

                const joinsText =
                    node.operator === '+' &&
                    (isText(node.left) || isText(node.right));

                if (
                    !joinsText &&
                    (isMoneyField(node.left) || isMoneyField(node.right))
                ) {
                    context.report({ node, message });
                }
            },
            UnaryExpression(node) {
                if (
                    (node.operator === '-' || node.operator === '+') &&
                    isMoneyField(node.argument)
                ) {
                    context.report({ node, message });
                }
            },
            AssignmentExpression(node) {
                if (
                    ARITHMETIC_ASSIGN.has(node.operator) &&
                    (isMoneyField(node.left) || isMoneyField(node.right))
                ) {
                    context.report({ node, message });
                }
            },
            CallExpression(node) {
                const names = memberPath(node.callee);
                const reducer = node.arguments[0];

                if (
                    names?.[names.length - 1] !== 'reduce' ||
                    !reducer ||
                    !['ArrowFunctionExpression', 'FunctionExpression'].includes(
                        reducer.type,
                    ) ||
                    reducer.params[0]?.type !== 'Identifier'
                ) {
                    return;
                }

                const total = reducer.params[0].name;
                const body =
                    reducer.body.type === 'BlockStatement'
                        ? reducer.body.body.find(
                              (statement) =>
                                  statement.type === 'ReturnStatement',
                          )?.argument
                        : reducer.body;
                const sum = unwrap(body);

                if (
                    sum?.type === 'BinaryExpression' &&
                    sum.operator === '+' &&
                    unwrap(sum.left)?.type === 'Identifier' &&
                    unwrap(sum.left).name === total
                ) {
                    context.report({ node, message });
                }
            },
        };
    },
);

const noDirectBackendCalls = rule(
    'React reaches the backend only through Wayfinder routes and Inertia',
    (context) => {
        const message =
            'React reaches the backend only through Wayfinder routes and Inertia: no hard-coded URLs, fetch or axios. Import the route from @/routes or @/actions. See docs/rules/frontend.md.';

        return {
            JSXAttribute(node) {
                const name = node.name?.name;
                const value =
                    node.value?.type === 'JSXExpressionContainer'
                        ? node.value.expression
                        : node.value;

                if (
                    (name === 'href' || name === 'action') &&
                    leadingText(value)?.startsWith('/')
                ) {
                    context.report({ node, message });
                }
            },
            CallExpression(node) {
                const callee = unwrap(node.callee);
                const names = memberPath(callee);

                if (
                    (callee.type === 'Identifier' && callee.name === 'fetch') ||
                    names?.join('.') === 'window.fetch'
                ) {
                    context.report({ node, message });

                    return;
                }

                if (
                    names?.length === 2 &&
                    names[0] === 'router' &&
                    leadingText(node.arguments[0])?.startsWith('/')
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
        'no-permission-checks': noPermissionChecks,
        'no-business-comparisons': noBusinessComparisons,
        'no-date-math': noDateMath,
        'no-money-math': noMoneyMath,
        'no-direct-backend-calls': noDirectBackendCalls,
        'component-boundaries': componentBoundaries,
    },
};
