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

/**
 * The props whose value a user reads or hears.
 */
const READABLE_PROPS = new Set([
    'title',
    'placeholder',
    'aria-label',
    'label',
    'alt',
]);

const hasLetters = (text) => /\p{L}/u.test(text);

/**
 * The /style page is a development tool and stays English: its own section
 * titles, specimen labels and the example data its demos receive. The
 * components it shows are translated.
 */
const isStylePage = (file) =>
    file.startsWith('resources/js/components/style/') ||
    file.startsWith('resources/js/pages/style/');

const noUntranslatedText = rule(
    'Text a user reads goes through t()',
    (context, file) => {
        if (isStylePage(file)) {
            return {};
        }

        const message = (what) =>
            `${what} is written straight into JSX. Write it in English through t('...') (or tChoice) from @/lib/i18n and add its Chinese to lang/zh_CN.json. See docs/rules/translations.md.`;

        return {
            JSXText(node) {
                if (hasLetters(node.value)) {
                    context.report({
                        node,
                        message: message(
                            `The text "${node.value.trim().slice(0, 40)}"`,
                        ),
                    });
                }
            },
            JSXAttribute(node) {
                const name = node.name?.name;

                if (typeof name !== 'string' || !READABLE_PROPS.has(name)) {
                    return;
                }

                const value = node.value;
                const literal =
                    value?.type === 'Literal'
                        ? value
                        : value?.type === 'JSXExpressionContainer'
                          ? unwrap(value.expression)
                          : null;

                if (
                    literal?.type === 'Literal' &&
                    typeof literal.value === 'string' &&
                    hasLetters(literal.value)
                ) {
                    context.report({
                        node,
                        message: message(
                            `The ${name} "${literal.value.slice(0, 40)}"`,
                        ),
                    });
                }

                if (
                    literal?.type === 'TemplateLiteral' &&
                    literal.quasis.some((quasi) => hasLetters(quasi.value.raw))
                ) {
                    context.report({
                        node,
                        message: message(`The ${name} text`),
                    });
                }
            },
        };
    },
);

/**
 * The UI rules a machine can see in the code (docs/golden-principles.md,
 * principle 10). They read the class names written in React: the `className`
 * attributes and the arguments of cn(), clsx(), cva() and twMerge().
 */
const CLASS_HELPERS = new Set(['cn', 'clsx', 'cva', 'twMerge', 'classNames']);

const SKIPPED_KEYS = new Set(['parent', 'loc', 'range', 'start', 'end']);

/**
 * Every string written inside an expression: literals, template parts and
 * object keys of conditions such as `cond && 'a b'` or `cn('a', { b: x })`.
 */
function stringsIn(node, found = []) {
    if (!node || typeof node !== 'object') {
        return found;
    }

    if (Array.isArray(node)) {
        node.forEach((child) => stringsIn(child, found));

        return found;
    }

    if (node.type === 'Literal' && typeof node.value === 'string') {
        found.push({ node, text: node.value });

        return found;
    }

    if (node.type === 'TemplateElement') {
        found.push({ node, text: node.value.raw });

        return found;
    }

    for (const [key, child] of Object.entries(node)) {
        if (!SKIPPED_KEYS.has(key) && child && typeof child === 'object') {
            stringsIn(child, found);
        }
    }

    return found;
}

/**
 * One class token split into its variants and its utility:
 * "md:dark:bg-blue-500" gives variants ["md", "dark"], utility "bg-blue-500".
 * Colons inside brackets ("data-[state=open]:x") do not split.
 */
function parseClass(token) {
    const variants = [];
    let depth = 0;
    let start = 0;

    for (let index = 0; index < token.length; index += 1) {
        const char = token[index];

        if (char === '[') {
            depth += 1;
        } else if (char === ']') {
            depth -= 1;
        } else if (char === ':' && depth === 0) {
            variants.push(token.slice(start, index));
            start = index + 1;
        }
    }

    return { variants, utility: token.slice(start).replace(/^[!-]+/, '') };
}

/**
 * Calls `check({ variants, utility }, node)` for every class token written in
 * this file, once per string.
 */
function classRule(description, check) {
    return rule(description, (context, file) => {
        const seen = new WeakSet();

        const visit = (expression, attribute) => {
            for (const { node, text } of stringsIn(expression)) {
                if (seen.has(node)) {
                    continue;
                }

                seen.add(node);

                for (const token of text.split(/\s+/).filter(Boolean)) {
                    check(
                        parseClass(token),
                        (message) => context.report({ node, message }),
                        { file, attribute },
                    );
                }
            }
        };

        return {
            JSXAttribute(node) {
                const name = node.name?.name;

                if (
                    typeof name === 'string' &&
                    (name === 'className' || name.endsWith('ClassName'))
                ) {
                    visit(node.value, name);
                }

                if (name === 'style') {
                    visit(node.value, 'style');
                }
            },
            CallExpression(node) {
                const callee = unwrap(node.callee);

                if (
                    callee?.type === 'Identifier' &&
                    CLASS_HELPERS.has(callee.name)
                ) {
                    visit(node.arguments, 'call');
                }
            },
        };
    });
}

const PALETTE =
    /^(?:bg|text|border|border-[xytblrse]|ring|ring-offset|fill|stroke|from|via|to|outline|divide|decoration|shadow|accent|caret|placeholder)-(?:slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose)-\d{2,3}(?:\/\d+)?$/;
const RAW_COLOUR = /#[0-9a-fA-F]{3,8}\b|\b(?:rgba?|hsla?|oklch|oklab)\(/;
const COLOUR_UTILITY =
    /^(?:bg|border|ring|fill|stroke|outline|divide|decoration|shadow|accent|caret|from|via|to)-|^text-(?!(?:xs|sm|base|lg|xl|\dxl|left|right|center|justify|start|end|wrap|nowrap|balance|pretty|ellipsis|clip|display)\b)/;

const noRawStyle = classRule(
    'Colours and sizes come from the theme tokens',
    ({ variants, utility }, report, { attribute }) => {
        if (attribute === 'style') {
            if (RAW_COLOUR.test(utility)) {
                report(
                    'A raw colour in a style. Use a theme token (bg-primary, text-muted-foreground, var(--chart-1)). See docs/design/foundations.md.',
                );
            }

            return;
        }

        if (PALETTE.test(utility) || RAW_COLOUR.test(utility)) {
            report(
                `"${utility}" is a raw colour. Use a theme token (bg-primary, text-muted-foreground, text-success). See docs/design/foundations.md.`,
            );
        }

        if (utility.startsWith('[') || /-\[/.test(utility)) {
            report(
                `"${utility}" is an arbitrary value. Use the theme's scale or a token, or the component's size variant. See docs/design/foundations.md.`,
            );
        }

        if (variants.includes('dark') && COLOUR_UTILITY.test(utility)) {
            report(
                `"dark:${utility}" overrides a colour for dark mode. The tokens in resources/css/app.css already change with the theme; use the token. See docs/design/foundations.md.`,
            );
        }
    },
);

const noDisplaySize = classRule(
    'The display sizes belong to Hero and PricingPlans',
    ({ utility }, report, { file }) => {
        if (
            /^text-display(?:-lg)?$/.test(utility) &&
            file !== 'resources/js/components/shared/hero.tsx' &&
            file !== 'resources/js/components/shared/pricing-plans.tsx'
        ) {
            report(
                'The display sizes belong to Hero and PricingPlans (components/shared). Use Hero or PricingPlans, or Heading for a title. See docs/design/foundations.md.',
            );
        }
    },
);

/**
 * The components with a `size` variant: a page never sets their height or padding.
 */
const SIZED_COMPONENTS = new Set([
    'Button',
    'Input',
    'PasswordInput',
    'Textarea',
    'SelectTrigger',
    'Toggle',
    'ToggleGroupItem',
    'Badge',
    'Checkbox',
    'InputOTPSlot',
    'DatePicker',
]);

const noComponentResize = rule(
    'A component is resized through its size variant',
    (context) => ({
        JSXOpeningElement(node) {
            const name = node.name?.name;

            if (typeof name !== 'string' || !SIZED_COMPONENTS.has(name)) {
                return;
            }

            for (const attribute of node.attributes) {
                if (attribute.name?.name !== 'className') {
                    continue;
                }

                for (const { text } of stringsIn(attribute.value)) {
                    for (const token of text.split(/\s+/).filter(Boolean)) {
                        const { variants, utility } = parseClass(token);

                        if (
                            /^(?:h|p|px|py|pt|pb|pl|pr|size)-/.test(utility) &&
                            !variants.some(
                                (variant) =>
                                    variant.startsWith('[') || variant === '*',
                            )
                        ) {
                            context.report({
                                node: attribute,
                                message: `"${token}" resizes ${name}. Use its size variant (size="sm", "lg", "icon") instead of a height or padding class. See docs/design/components.md.`,
                            });
                        }
                    }
                }
            }
        },
    }),
);

const iconButtonLabel = rule(
    'An icon-only Button has an aria-label',
    (context) => ({
        JSXOpeningElement(node) {
            if (node.name?.name !== 'Button') {
                return;
            }

            const size = node.attributes.find(
                (attribute) => attribute.name?.name === 'size',
            );
            const value = size?.value;

            if (
                value?.type !== 'Literal' ||
                typeof value.value !== 'string' ||
                !value.value.startsWith('icon')
            ) {
                return;
            }

            const named = node.attributes.some((attribute) =>
                ['aria-label', 'aria-labelledby'].includes(
                    attribute.name?.name,
                ),
            );

            if (!named) {
                context.report({
                    node,
                    message:
                        "An icon-only Button needs aria-label={t('...')}, so a screen reader can name it (and a Tooltip for sighted users). See docs/design/components.md.",
                });
            }
        },
    }),
);

const formNoValidate = rule('Every form sets noValidate', (context) => ({
    JSXOpeningElement(node) {
        if (node.name?.name !== 'Form' && node.name?.name !== 'form') {
            return;
        }

        const attribute = node.attributes.find(
            (item) => item.name?.name === 'noValidate',
        );
        const off =
            attribute?.value?.type === 'JSXExpressionContainer' &&
            attribute.value.expression?.value === false;

        if (!attribute || off) {
            context.report({
                node,
                message:
                    "Add noValidate to the form, so the browser never shows its own message in its own language; the server answers in the app's language. Keep `required` on the input. See docs/rules/frontend.md.",
            });
        }
    },
}));

const noDateInput = rule(
    'A date field is the shared DatePicker',
    (context) => ({
        JSXOpeningElement(node) {
            if (node.name?.name !== 'input' && node.name?.name !== 'Input') {
                return;
            }

            const type = node.attributes.find(
                (attribute) => attribute.name?.name === 'type',
            );

            if (
                type?.value?.type === 'Literal' &&
                type.value.value === 'date'
            ) {
                context.report({
                    node,
                    message:
                        'Use the shared DatePicker (components/shared/date-picker.tsx), which reads and writes YYYY-MM-DD, instead of type="date". See docs/design/patterns/form.md.',
                });
            }
        },
    }),
);

export default {
    meta: { name: 'ezdev' },
    rules: {
        'no-direct-backend-calls': noDirectBackendCalls,
        'component-boundaries': componentBoundaries,
        'no-untranslated-text': noUntranslatedText,
        'no-raw-style': noRawStyle,
        'no-display-size': noDisplaySize,
        'no-component-resize': noComponentResize,
        'icon-button-label': iconButtonLabel,
        'form-no-validate': formNoValidate,
        'no-date-input': noDateInput,
    },
};
