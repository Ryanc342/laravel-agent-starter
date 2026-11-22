/**
 * Ezdev's rules for oxlint. vite.config.ts loads this file under
 * lint.jsPlugins:
 *
 * - "ezdev/component-boundaries" (.claude/rules/skeleton/comp.md, COMP-8):
 *   domain components never import another domain's components,
 *   components/shared never imports domain components, and nothing imports a
 *   page.
 *
 * - "ezdev/submit-button": only SubmitButton sends a form. No other element
 *   says type="submit", and a plain <button> says its type, since a
 *   <button> without one sends its form.
 *
 * Only these rules and import/no-cycle are oxlint's machine rules; the cards
 * of .claude/rules/skeleton/ name each.
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

/** The text of a JSX attribute's value when it is written out, else null. */
function attributeText(attribute) {
    const value =
        attribute.value?.type === 'JSXExpressionContainer'
            ? attribute.value.expression
            : attribute.value;

    if (value?.type === 'Literal' && typeof value.value === 'string') {
        return value.value;
    }

    if (value?.type === 'TemplateLiteral' && value.expressions.length === 0) {
        return value.quasis[0].value.cooked;
    }

    return null;
}

const submitButton = rule('Only SubmitButton sends a form', (context, file) => {
    if (file === 'resources/js/components/shared/submit-button.tsx') {
        return {};
    }

    return {
        JSXOpeningElement(node) {
            const type = node.attributes.find(
                (attribute) =>
                    attribute.type === 'JSXAttribute' &&
                    attribute.name.name === 'type',
            );

            if (type !== undefined && attributeText(type) === 'submit') {
                context.report({
                    node: type,
                    message:
                        'Only SubmitButton (components/shared/submit-button.tsx) sends a form: it is disabled and shows a spinner while the form is sent. Use it instead of type="submit".',
                });

                return;
            }

            if (
                node.name.type === 'JSXIdentifier' &&
                node.name.name === 'button' &&
                type === undefined
            ) {
                context.report({
                    node,
                    message:
                        'A <button> without a type sends its form. Give it type="button", or use SubmitButton when it is meant to send the form.',
                });
            }
        },
    };
});

export default {
    meta: { name: 'ezdev' },
    rules: {
        'component-boundaries': componentBoundaries,
        'submit-button': submitButton,
    },
};
