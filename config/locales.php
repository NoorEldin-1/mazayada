<?php

/*
|--------------------------------------------------------------------------
| Mazayada Locale Configuration
|--------------------------------------------------------------------------
|
| Single source of truth for the platform's supported languages. The whole
| i18n stack reads from here: the SetLocale middleware, the language switcher
| component, the layouts (html dir/lang), and the locale helpers.
|
| To add a new language later, add its code to `supported` and a `meta` entry,
| then create lang/<code>/*.php files. The key-parity test (tests/Feature/
| LocalizationTest.php) will tell you exactly which keys are still missing.
|
| Arabic is the platform's primary language and the translation fallback.
|
*/

return [

    // The locales the app will accept. Anything outside this list falls back
    // to `default`. Order here is the order shown in the language switcher.
    'supported' => ['ar', 'fr', 'en'],

    // Primary language + the fallback used when a key is missing in the active
    // locale. Both are Arabic by design (this is an Algerian gov platform).
    'default' => 'ar',

    // Per-locale presentation metadata.
    //   native : how the language names itself (shown in the switcher menu)
    //   label  : short code shown on the compact switcher (AR / FR / EN)
    //   dir    : text direction — drives <html dir> and CSS logical layout
    //   font   : 'arabic' | 'latin' — drives the <body> font-family
    'meta' => [
        'ar' => ['native' => 'العربية',  'label' => 'AR', 'dir' => 'rtl', 'font' => 'arabic'],
        'fr' => ['native' => 'Français', 'label' => 'FR', 'dir' => 'ltr', 'font' => 'latin'],
        'en' => ['native' => 'English',  'label' => 'EN', 'dir' => 'ltr', 'font' => 'latin'],
    ],

];
