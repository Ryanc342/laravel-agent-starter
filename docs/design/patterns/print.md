# Print page

Use for something the user prints or saves as a PDF: an invoice, a receipt, a task sheet, a report. The page for one record on screen stays the detail page (detail.md); this is its printable version.

## Layout, top to bottom

1. A bar above the sheet with "Print" (`default`) and "Download PDF" (`outline`). The bar is hidden when printing.
2. The sheet: white, A4 proportions, the record's name as `<Heading>`, its facts as a `dl`, and its lines as a plain table with horizontal lines. An invoice sheet adds the seller and buyer, the line items, and the totals right-aligned in `tabular-nums`.

## Printing

- "Print" calls the browser's print. Print styles hide the app chrome (sidebar, top bar, the bar above the sheet), remove the page padding, set A4 and keep black text on white in either theme.
- A table row is never split across two pages, and the header row repeats on each page.

## PDF

- "Download PDF" is a backend route that returns `application/pdf`, made by `spatie/laravel-pdf` with the DOMPDF driver (`LARAVEL_PDF_DRIVER=dompdf` in `.env`). DOMPDF reads CSS 2.1 only: no flexbox and no grid, so the PDF's Blade view is laid out with tables and keeps the same order and facts as the on-screen sheet.
- DOMPDF's built-in fonts (DejaVu Sans) have no Chinese characters: a PDF that must show Chinese text needs a CJK font installed for DOMPDF (`laravel-pdf.dompdf.font_dir`, a project decision), or another driver such as Browsershot. The starter does not ship a CJK font.
- The file name says what it is ("task-42.pdf", "invoice-2026-0012.pdf").

## Rules

- Totals, tax and every figure come from the backend; the sheet and the PDF both show the same values and neither calculates.
- A record the user may not view is a 403 (the Policy decides), for the print page and the PDF alike.

## Phone

The sheet scrolls inside its own box if it is wider than the screen; the page never scrolls sideways.

## Sample and demo

The task print page: `docs/reference/tasks/resources/js/pages/tasks/print.tsx` and its PDF view `docs/reference/tasks/resources/views/tasks/pdf.blade.php`. Demo: `resources/js/components/style/print-demo.tsx` (an invoice sheet).
