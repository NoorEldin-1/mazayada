# E2E Test Report — Resolution Summary

**Report date:** 2026-08-01 · **Fixed:** 2026-08-02
**Status:** all 11 reported issues resolved · test suite 348 passed (was 338; 10 regression tests added)

---

## 1. Functional Bugs

### 1.1 System Settings save crashes with HTTP 500 — `Critical` ✅ Fixed

**Root cause:** `AuditLog::log()` declared a non-nullable `string $resourceId`.
Saving settings is a platform-wide action with no resource row, so the
controller passed `null` → `TypeError` → 500. This was the only one of 75 call
sites passing null.

**Fix:** `AuditLog::log()` now accepts `?string $resourceId = null` and stores
`'-'` (the column is NOT NULL). The settings save is audited with the list of
changed keys.

**Also found while fixing this (not in the report):**

| Issue | Impact |
|---|---|
| `$request->boolean("settings.{$key}")` used dot notation on keys that literally contain dots (`security.enforce_admin_2fa`) | **Every boolean setting was silently saved as OFF.** Enabling staff 2FA or NIN checksum enforcement never persisted. |
| No validation on submitted values | A typo in a numeric field was cast to `0` and applied to fee/security maths. |

Both fixed: the payload is read from the raw array, and values are validated
per declared type (`int` / `float` / `string`) before saving.

---

### 1.2 Submitting an empty Inspection answer silently does nothing — `High` ✅ Fixed

**Root cause:** `layouts/admin.blade.php` rendered **no flash region and no
`$errors` block at all**. Every controller returning `back()->with('success')`
or a failed validation redirect landed on a page that displayed neither — so
successful saves and rejected submissions looked identical to "nothing
happened". This affected the whole admin panel, not just inspections.

**Fix:**
- New shared `<x-ui.flash />` (success / error / consolidated validation
  summary) rendered once by the admin layout. 8 duplicated per-page copies
  removed.
- Failed modal submissions now carry their modal id back (`session('open_modal')`)
  so the modal re-opens with the error visible where the user typed.
- Client-side guard in `dashboard.js`: a modal form that fails validation gets
  an inline message per invalid field instead of an inert-looking button.

---

### 1.3 KYC data inconsistency between lists — `High` ✅ Fixed

**Root cause:** not a data bug — a labelling one. `PENDING` means *the citizen
has not uploaded documents yet*; the review queue correctly lists only
`UNDER_REVIEW`. But `PENDING` was translated as "Pending / في الانتظار", which
reads as "waiting on the admin".

**Fix:**
- Relabelled: **"Not submitted"** / «لم تُقدَّم الوثائق» / «Non soumis».
- Dashboard counter relabelled "Verification requests awaiting review".
- KYC queue gained status filter tabs with live counts (Under review · Not
  submitted · Verified · Rejected · Suspended · All) and a Status column, so
  every account in the Users list is now reachable and explained from the queue.

---

## 2. UI Issues

### 2.1 Stray "MP4 File" overlay on the auction detail page — `Medium` ✅ Fixed

**Root cause:** the thumbnail strip used `class="swiper ad-thumbs"`. The `ad-`
prefix matches ad-blocker cosmetic filters, which hid the strip — leaving its
`<video>` element with no layout box, so the browser's media download badge
anchored to the page origin (top-left). This explains the "detached,
intermittent, disappears after re-render" behaviour, and why it only appeared
in some browsers.

**Fix:** renamed `ad-thumbs` → `mzd-thumbs` (blade + CSS), added
`controlsList="nodownload"` and `disablePictureInPicture` to all gallery video
elements. A code comment now records why the prefix must never be `ad-*`.

### 2.2 Media gallery defaults to an unplayed video shown as a black frame — `Medium` ✅ Fixed

**Fix:** the hero video now carries a `poster` (the first photo when one exists)
and a `#t=0.1` media fragment so it paints a real frame instead of solid black.

### 2.3 Auction thumbnails do not render on the public browse list — `Medium` ✅ Fixed

**Root cause:** those auctions genuinely have no photos uploaded — the query
loads the column correctly. The fallback was a bare pencil glyph, which reads
as broken imagery.

**Fix:** new `Auction::thumbnailMedia()` — falls back to a still frame of the
asset video when no photo exists, otherwise renders an explicit labelled
"No image" placeholder. Applied to the browse list and the homepage cards.

### 2.4 Internal configuration keys exposed as user-facing text — `Low` ✅ Fixed

**Fix:** the technical key (`bidding.extension_duration_minutes`, …) moved from
printed text into the label's tooltip. All 18 keys already had human labels in
all three locales.

---

## 3. UX Issues

### 3.1 Create-Auction validation jumps field-by-field with no summary — `Medium` ✅ Fixed

**Fix:** `novalidate` on the create/edit forms so the server validates
everything in one pass; all errors return at once as the consolidated summary
(new shared component) plus the inline per-field messages the form already had.
The page auto-scrolls to the summary on failure.

### 3.2 Misleading / hardcoded homepage statistics — `Medium` ✅ Fixed

**Root cause:** `HomeController` already computed real statistics — the view
ignored them and printed hand-written figures (`+2400`, `5`, `+320`…).

**Fix:** hero figures are now live counts (wilayas, active institutions, public
auctions). The institutions strip is built from the real `entities` table with
each body's actual published-auction count, and hides itself when empty.

### 3.3 Price display inconsistency for the same auction — `Medium` ✅ Fixed

**Root cause:** the two views were showing **different figures by design** under
the same generic "Price" header — admin showed `opening_price`, public pages
showed `currentPrice()` (opening + bids). The 7,006,000 vs 20,000 case is
exactly that. The 3-trillion figure is a data-entry error that passed because
`opening_price` had `min:0` and no upper bound.

**Fix:**
- Admin list now has two explicitly labelled columns: **Opening price** and
  **Current price**.
- New sanity bound `mazayada.limits.max_price_dzd` (default 10,000,000,000 DZD),
  enforced on create and update, with the limit stated under the field.

> Note: the existing 3-trillion record predates the bound and was left
> untouched — it is production data, and correcting it is an admin decision.

### 3.4 Untranslated user-generated content in non-Arabic locales — `Low` ✅ Fixed

**Fix:** the Arabic fallback is kept (making FR/EN mandatory would block
publishing), but user-generated text now renders with `dir="auto"` so Arabic
inside an LTR page keeps its correct direction and punctuation placement. The
auction detail page was also hardcoded to `title_ar`; it now uses the localized
accessor. The admin form states what happens when a translation is left blank.

---

## Verification

```bash
php artisan test                            # 348 passed (1210 assertions)
php artisan test --filter=LocalizationTest  # key parity across ar/fr/en
npm run build                               # Vite bundle rebuilt
```

Regression tests added: `AdminSettingsTest` (4), `HomePageStatsTest` (2), plus
new cases in `KycTest`, `InspectionDeliveryTest` and `AdminAuctionStoreTest`.

**Not reproduced / not changed:** nothing. All 11 findings were confirmed in the
codebase and addressed.
