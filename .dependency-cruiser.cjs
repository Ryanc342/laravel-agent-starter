/**
 * Frontend boundaries (docs/rules/frontend.md). Each rule's comment says what to
 * do instead; dependency-cruiser prints it when the rule breaks.
 *
 * @type {import('dependency-cruiser').IConfiguration}
 */
module.exports = {
    forbidden: [
        {
            name: 'no-cross-domain-components',
            severity: 'error',
            comment:
                "A domain's components may not import another domain's components. Move the shared part to resources/js/components/shared/.",
            from: { path: '^resources/js/components/([^/]+)/' },
            to: {
                path: '^resources/js/components/[^/]+/',
                pathNot: [
                    '^resources/js/components/$1/',
                    '^resources/js/components/(ui|shared)/',
                ],
            },
        },
        {
            name: 'shared-components-stay-generic',
            severity: 'error',
            comment:
                'components/ui and components/shared are used by every domain, so they may not import a domain component. Pass data in as props instead.',
            from: { path: '^resources/js/components/(ui|shared)/' },
            to: {
                path: '^resources/js/components/[^/]+/',
                pathNot: '^resources/js/components/(ui|shared)/',
            },
        },
        {
            name: 'no-importing-pages',
            severity: 'error',
            comment:
                'Pages are entry points; nothing imports a page. Move the shared part into resources/js/components/.',
            from: {},
            to: { path: '^resources/js/pages/' },
        },
        {
            name: 'no-circular',
            severity: 'error',
            comment:
                'Circular imports make modules hard to change. Move the shared part into a file both can import.',
            from: {},
            to: { circular: true },
        },
    ],
    options: {
        doNotFollow: { path: 'node_modules' },
        exclude: { path: '^resources/js/(actions|routes|wayfinder)/' },
        tsPreCompilationDeps: true,
        tsConfig: { fileName: 'tsconfig.json' },
        enhancedResolveOptions: {
            exportsFields: ['exports'],
            conditionNames: ['import', 'require', 'node', 'default', 'types'],
            extensions: ['.ts', '.tsx', '.js', '.jsx', '.d.ts'],
        },
    },
};
