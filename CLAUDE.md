# CLAUDE.md

Guidance for working in this repository.

## Project

Mazayada — the national digital platform for public auctions and leases in
Algeria. Laravel 11 + Blade + Tailwind 4, Fortify (auth), Spatie Permission
(roles), Reverb (realtime). Primary language is **Arabic (RTL)**.

## Internationalization (i18n) — REQUIRED for all UI work

The platform is **trilingual: Arabic (`ar`, primary), French (`fr`), English
(`en`)**. When adding or changing any user-facing text:

- **Never hardcode strings.** Use `__('group.key')` (semantic keys), never raw
  Arabic/French/English literals in Blade or PHP.
- **Add the key to all three locales:** `lang/ar/<group>.php`,
  `lang/fr/<group>.php`, `lang/en/<group>.php`. Write the Arabic value first.
- **Reference data:** use the localized `$model->name` accessor (from
  `HasLocalizedName`) — never `->name_ar` in new code.
- **CSS:** use logical properties (`margin-inline`, `inset-inline-*`,
  `text-align: start/end`), never physical `left`/`right`, so RTL↔LTR mirrors.
- **New layouts:** root element must be `<html lang="{{ locale_lang() }}"
  dir="{{ locale_dir() }}">` and include `<x-lang-switcher />`.
- **Verify:** `php artisan test --filter=LocalizationTest` enforces key parity
  across locales and must pass before pushing.

Full reference: **[docs/i18n.md](docs/i18n.md)**.

## Tests

```bash
php artisan test            # full suite
php artisan test --filter=LocalizationTest
```
