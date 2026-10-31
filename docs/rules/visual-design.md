# Visual design

Applies to `resources/js/**`, `resources/css/**` and `vite.config.ts`, and only when a `DESIGN.md` (any capitalisation) exists in the project root.

`DESIGN.md` describes how the app should look. It controls appearance only. It never changes the architecture, business rules, folder structure or data contract; if it conflicts with them, they win. Do not edit `DESIGN.md` unless the user asks.

## The first time you build a page with a DESIGN.md

1. Move its colors, fonts and corner radius into the theme variables in `resources/css/app.css` (Tailwind `@theme` and the light and dark CSS variables the shadcn components already read).
2. Load fonts the way `resources/views/app.blade.php` already does.
3. Tell the user which tokens you set.

## Every page after that

- Use the theme tokens (`bg-primary`, `text-muted-foreground`, `rounded-lg`), never raw colors or pixel values from DESIGN.md.
- Use the shadcn components in `components/ui` and restyle them through tokens. Do not install another UI library.
- Keep light and dark mode working.

## Without a DESIGN.md

Keep the starter kit's look. Do not invent a visual style and do not ask the user for one.
