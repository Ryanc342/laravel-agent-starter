# Visual design

Applies to `resources/js/**`, `resources/css/**`, `DESIGN.md` and `docs/design/**`.

`DESIGN.md` in the project root is the style of every screen and an index of the design spec. The details are in `docs/design/`: `foundations.md` (colour, type, language, space, shape, motion, copy, messages), `components.md` (which component and variant for what) and one file per page pattern in `docs/design/patterns/`. The spec controls appearance only; the architecture, business rules, folders and the data contract win over it. A project with its own brand changes the primary colour and the logo and nothing else. These files are protected: change them only with the user's approval.

## The theme is the one place for values

- Every colour, radius, font and spacing value is defined once, in `resources/css/app.css`: light values in `:root`, dark values in `.dark`, mapped to Tailwind classes in `@theme`. The spec files name tokens and say when to use them; they never repeat a value. To change a value, change it in app.css only.
- Fonts load through the `fonts` list in vite.config.ts.
- Use the theme classes (`bg-primary`, `text-muted-foreground`, `text-success`, `rounded-lg`), never raw colors (`bg-blue-500`, `#hex`), arbitrary values in square brackets or `dark:` color overrides, and resize a component only through its `size` variant. The reviewers check each of these (lane `look`).
- The spec files name tokens and component sizes and never a pixel value or a Tailwind size class.

## Every page

- Read DESIGN.md first. Its index names the files for your task: foundations.md for colour, type, space and messages, components.md for which component to use, and the pattern file of the page you build. Copy the structure of the sample or demo the pattern file names. A page that matches no pattern is built freely from foundations.md and components.md; tell the user, who adds its pattern later.
- Use `components/ui` (shadcn) and `components/shared`, and restyle them through their variants. Do not install another UI library.
- Keep light and dark mode working, and keep every screen working with English and Chinese text.
- Compare the page with the style overview page (`/style`, development only): its token, component and pattern demos show how each should look.
- The page check with Playwright is the final review's `look` and `behaviour` rounds (docs/workflow.md, step 4): the reviewers open each changed page at desktop (1280px) and phone (390px) width against principle 10 in docs/golden-principles.md (docs/prompts/review.md, step 7). Make no browser pass of your own before them; fix what they find.
- Whoever opens a page uses a configured isolated headless Playwright session (`playwright`, or `playwright-review` for the review copy), never the user's browser. Take screenshots without a filename so they go to the session's temporary output directory. Inspect them before closing the browser; `browser_close` removes that session's temporary output. Always close the browser after the check, including when an error occurs. The launcher also closes it after a tool error; there is no idle timeout, so a forgotten close can leave the browser and screenshots until the server stops. If the user explicitly asks to keep a screenshot, save it directly to the requested destination. Do not delete other sessions' files or the user's existing files. A stopped server cleans its own output; the next server run cleans abandoned session directories.

`resources/js/pages/welcome` is Laravel's own start page with its own colors. Replace it when the project gets a home page; do not copy from it.
