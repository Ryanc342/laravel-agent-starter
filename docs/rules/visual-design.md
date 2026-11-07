# Visual design

Applies to `resources/js/**`, `resources/css/**` and `DESIGN.md`.

`DESIGN.md` in the project root says how every screen looks. It uses Google's DESIGN.md format (github.com/google-labs-code/design.md): exact values (tokens) at the top, then eight sections that explain them, ending with Do's and Don'ts. It controls appearance only; the architecture, business rules, folders and the data contract win over it. The template ships one for a quiet work tool. A project with its own brand replaces it, keeping the section order. It is a protected file: change it only with the user's approval.

## The tokens are the theme

- Every color, radius and font in DESIGN.md is a theme variable in `resources/css/app.css`: light values in `:root`, dark values in `.dark`, mapped to Tailwind classes in `@theme`. A change to one is a change to the other, in the same commit.
- Fonts load through the `fonts` list in vite.config.ts.
- Use the theme classes (`bg-primary`, `text-muted-foreground`, `text-success`, `rounded-lg`, `gap-4`), never raw colors (`bg-blue-500`, `#hex`), arbitrary sizes (`text-[13px]`) or `dark:` color overrides.

## Every page

- Read DESIGN.md before building a page: Layout says how list, detail and form pages look, Components which component to use for what, Do's and Don'ts what to avoid.
- Copy the layout of the matching page in docs/reference/tasks or resources/js/pages/notifications.
- Use `components/ui` (shadcn) and `components/shared`, and restyle them through their variants. Do not install another UI library.
- Keep light and dark mode working.
- Before you report, open the page with the Playwright MCP server at desktop (1280px) and phone (390px) width and fix what looks wrong. The reviewer does the same (docs/prompts/review.md step 5) against principle 10 in docs/golden-principles.md.
- Use the configured isolated headless Playwright session, never the user's browser. Take screenshots without a filename so they go to the session's temporary output directory. Inspect them before closing the browser; `browser_close` removes that session's temporary output. Always close the browser after the check, including when an error occurs. The launcher also closes it after a tool error; there is no idle timeout, so a forgotten close can leave the browser and screenshots until the server stops. If the user explicitly asks to keep a screenshot, save it directly to the requested destination. Do not delete other sessions' files or the user's existing files. A stopped server cleans its own output; the next server run cleans abandoned session directories.

`resources/js/pages/welcome` is Laravel's own start page with its own colors. Replace it when the project gets a home page; do not copy from it.
