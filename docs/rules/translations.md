# Translations (English and Simplified Chinese)

Applies to every sentence a user reads in `app/**`, `resources/**`, `routes/**` and `docs/reference/tasks/`.

## One source

- Two languages: English (`en`) and Simplified Chinese (`zh_CN`). English is the language of the code; Chinese is a translation.
- A sentence is keyed by its English text. Write the English in the code and add the Chinese in the same commit: `lang/zh_CN.json` holds `"Save changes": "保存更改"`. English needs no JSON file.
- Laravel's own groups (`validation`, `auth`, `passwords`, `pagination`) live in `lang/en/*.php` and `lang/zh_CN/*.php`. Validation `attributes` name each field so a message reads in Chinese.
- No translation package. React gets the current language's strings as a shared Inertia prop and reads them with `t(key, replace)` and `tChoice(key, count, replace)` from `resources/js/lib/i18n.ts`, which follow Laravel's `:name` placeholders and plural syntax.

## Write a sentence

- React: `t('Save changes')`, `t('Delete :name', { name })`, `tChoice('{1} :count task|[2,*] :count tasks', n)`. Labels, buttons, headings, states, `aria-label`s, `title`, `placeholder`, `alt` and toasts written in React all go through `t()`, never straight into JSX.
- PHP: `__('Task created.')`, `trans_choice(...)`: toasts, validation messages, notifications, mails and the PDF's labels.
- Module-level data (a nav list, a breadcrumb in `Page.layout`) never calls `t()` at import time, because the language is not set yet: mark each sentence with `msg('Dashboard')` (a no-op marker from `@/lib/i18n`) and call `t(item.title)` where it is shown. oxlint reports a `t()` or `tChoice()` outside every function (`ezdev/no-module-level-t`).
- A stored notification builds its data with `$this->sentence('Welcome to :app.', ['app' => ...])` (`InAppNotification`): the English sentence and its values are stored and translated when read.
- Use `:name` placeholders, never string concatenation, so Chinese can reorder the words.
- A sentence passed to `t()`, `tChoice()`, `msg()`, `__()`, `trans()`, `trans_choice()`, `@lang` or `sentence()` that is missing from `lang/zh_CN.json`, a JSON entry nothing uses, a Chinese sentence whose `:placeholders` differ from its key, and a key of `lang/en/{validation,auth,passwords,pagination}.php` missing from `lang/zh_CN/` are each a finding for the reviewers (lane `behaviour`). Laravel's and Fortify's own mail and message sentences count as used.

## Never translate user content

What users typed (a task's title, a name, a note) is shown as it came. Dates and numbers are formatted with `Intl` in the current language through `lib/format.ts`, never by hand.

## Which language

In this order: the signed-in user's `locale`, the `locale` cookie, the browser's `Accept-Language` (any Chinese gives `zh_CN`), then `en`. The `SetLocale` middleware applies it; `locale.update` saves a choice on the user and in the cookie.

## Notifications

A notification stores its English sentence and its values, not a finished sentence; the Resource translates it in the reader's language when it is shown.

## Tests

Tests run in English (no `Accept-Language`, no cookie). A test of Chinese sets the locale itself.

## The /style page

`/style` is translated like every page: its section titles and specimen labels go through `t()` and have their Chinese in `lang/zh_CN.json`, like every file. Example data a demo receives counts as user content and is shown as it is, as for any page.
