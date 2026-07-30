# تغييرات الـ API المطلوبة لتطبيق الموبايل — **منفّذة**

ردّ عملي مرتّب **بنفس ترتيب الطلب**. كل بند فيه: كان → بقى → الرد الجديد.
الأدلة من سورس الـ repo. **16 / 16 بند منفّذ**، ومتغطّي بـ 29 تست في
`tests/Feature/Api/V1/MobileClientContractTest.php`.

> **التشخيص الجذري في الطلب كان قريب بس مش مضبوط:** الحارس `sanctum` **موجود**
> أصلاً — Sanctum بيسجّله بنفسه وقت التشغيل. المشكلة إن المسارات العامة مالهاش
> حارس خالص، فـ `$request->user()` بيقع على الحارس الافتراضي `web` (جلسة)،
> والموبايل مالوش جلسة. الحل كان middleware اختياري مش تعديل `config/auth.php`
> — التفصيلة في BE-15 وهي مهمة.

---

## الخلاصة

| # | Endpoint | التغيير | الحالة |
|---|----------|---------|--------|
| **BE-15** | `GET /auctions/{id}` | `meta.viewer` بيتعبّى بالتوكن | ✅ |
| **BE-13** | بوّابات الدفع | return URL للموبايل + `/status` بيقبل الاتنين | ✅ |
| **BE-11** | `POST/DELETE /devices` | جديد + قناة Push كاملة | ✅ |
| **BE-16** | مسارات الدفع | `code` ثابت في كل رفض | ✅ |
| **BE-3** | `GET /my-auctions` | حالة مشاركة كاملة + تبويب `all` | ✅ |
| **BE-2** | `GET /notifications` | `type` ديناميّ | ✅ |
| **BE-1** | `GET /auctions` | `requires_commerce_register` | ✅ |
| **BE-12** | `GET /reports/summary` | توحيد الوحدة على الدينار | ⚠️ كاسر |
| **BE-8** | `GET /profile` | حقول KYC للتعبئة المسبقة | ✅ |
| **BE-6** | `GET /dashboard` | `final_price` + `closed_at` | ✅ |
| **BE-14** | `GET /ping` | إعدادات Reverb العامة | ✅ |
| **BE-4** | `GET /documents/filters` | جديد | ✅ |
| **BE-5** | إشعار السجل التجاري | Push + `type` | ✅ |
| **BE-7** | `GET /reports/export/{format}` | جديد | ✅ |
| **BE-9** | `GET /verify` | نسخة JSON | ✅ |
| **BE-10** | `auction.{id}.user.{id}` | بثّ فعلي | ✅ |

**تغيير كاسر واحد بس: BE-12.** الباقي كله إضافات.

---

## 🔴 BE-15 · `GET /api/v1/auctions/{id}`

**كان:** `meta.viewer = null` و`has_book_access = false` لأي مستخدم موبايل.

**السبب الفعلي:** مش نقص الحارس. `SanctumServiceProvider` بيعمل merge لـ
`auth.guards.sanctum` وقت التشغيل، فالحارس موجود. المشكلة إن مجموعة المزادات
العامة مالهاش middleware مصادقة أصلاً — فـ `$request->user()` بيتحل عبر
`config('auth.defaults.guard')` = `web` = جلسة = null للموبايل.

**بقى:** middleware `token.optional` (`app/Http/Middleware/ResolveOptionalToken.php`)
على مجموعة المزادات العامة في `routes/api.php`:

```php
Route::prefix('auctions')->name('auctions.')->middleware('token.optional')->group(...)
```

بيقرا التوكن لو موجود ويحطّ المستخدم على الريكوست، **ومابيرفضش أبدًا** — توكن
ناقص/منتهي/غلط بيسيب الطلب كضيف، فالعقد العام للمسار ما اتغيّرش.

**شروط القبول:** التوكن لازم يكون `access` (توكن `refresh` مش جلسة) + الحساب
نشط (نفس ضمانات `EnsureActiveAccount`). غير كده = ضيف.

> ⚠️ **الاقتراح بإضافة `sanctum` لـ `config/auth.php` اتجرّب واتشال.**
> إضافة `'provider' => 'users'` للحارس بتخلي spatie/laravel-permission يعتبره
> حارس صالح للموديل، و`Illuminate\Auth\Middleware\Authenticate` بينادي
> `shouldUse('sanctum')` مع كل طلب بتوكن — فكل فحوصات الأدوار بتقع:
> `There is no role named SUPER_ADMIN for guard sanctum`. وده كان هيضرب
> **الإنتاج** مش التستات بس. السبب متسجّل كتعليق في `config/auth.php` عشان
> محدش يعيدها.

**الرد الجديد** (نفس البنية، متعبّية):

```jsonc
{
  "data": { "...": "...", "has_book_access": true },
  "meta": {
    "viewer": {
      "can_bid": true,
      "is_participant": true,
      "has_commerce_register": false,
      "commerce_register_blocked": false,
      "has_book_access": true,
      "book_purchased": true,
      "deposit_paid": true,
      "is_winner": false,
      "can_appeal": false,
      "existing_appeal": null,
      "has_final_payment": false
    }
  }
}
```

**بونَس:** `data.award_document` كان بيعتمد على نفس المستخدم — بقى بيتعبّى للفائز
كمان من غير تغيير إضافي.

---

## 🔴 BE-13 · بوّابات الدفع (return URL)

**كان:** الثلاث بوّابات بتستخدم `route('payments.callback')` المحمي بـ `auth`
(جلسة) — الـ WebView بيقع على اللوجين.

**بقى:** `PaymentService` بياخد باراميتر `$channel` (`'web'` افتراضي / `'api'`)
وبيبني منه `success_url` + `failure_url` ويمرّرهم للبوّابة عبر الـ context.
كنترولرز الـ API بتبعت `'api'`:

```php
// app/Services/PaymentService.php
private function returnUrls(Payment $payment, string $channel): array
{
    $route = $channel === 'api' ? 'api.v1.payments.callback' : 'payments.callback';
    return [
        'success_url' => route($route, ['ref' => $payment->id, 'decision' => 'success']),
        'failure_url' => route($route, ['ref' => $payment->id, 'decision' => 'fail']),
    ];
}
```

الثلاث بوّابات بقت بتحترم `$context['success_url'] ?? <القديم>` — فالويب ما
اتغيّرش خالص. و`CibWebGateway` بقى بيمرّر `ref` و`decision` (وكمان `failUrl`)
بدل `returnUrl` فاضي.

**مسار الرجوع للموبايل:**
```
GET /api/v1/payments/callback?ref={payment_id}&decision=success|fail
```
عام، بدون جلسة، idempotent، وبيعيد التحقق من البوّابة نفسها — فالـ query string
المزوّرة ماتقدرش تأكّد دفعة.

**مش لازم تسيب الصفحة تفتح:** كفاية تكتشف البادئة `/api/v1/payments/callback`
في الـ WebView، تقفلها، وتعمل poll على `/status`. التأكيد الرسمي جاي من الـ
webhook الموقّع بره الدورة دي أصلاً.

### السؤال بتاع الـ ref — متحلّ

`GET /payments/{ref}/status` بقى بيقبل **الاتنين**: `gateway_ref` أو `payment id`
(نفس ما `handleCallback` بيعمل). وبيرجّع مفتاحين جداد:

```jsonc
{
  "ref": "<اللي بعتّه>",
  "gateway_ref": "MOCK-REF-123",   // جديد — المرجع الرسمي، وحّد عليه
  "confirmed": true,                // جديد — كل الصفوف مأكّدة؟
  "payments": [ ... ]
}
```

الحماية زي ما هي: دفعة مستخدم تاني = 404.

---

## 🔴 BE-11 · `POST/DELETE /api/v1/devices` — جديد

```
POST   /api/v1/devices   { token, platform: "ios"|"android", locale?, app_version? }  → 204
DELETE /api/v1/devices   { token }                                                     → 204
GET    /api/v1/devices/status                                                          → { push_enabled, devices }
```

**سلوكيات مهمة:**

- **idempotent** — ناديه كل مرة التطبيق يفتح وكل مرة التوكن يتجدّد.
- **التوكن هو الهوية** (unique عالمي): لو نفس الجهاز سجّل دخول بحساب تاني، الصف
  بيتحوّل للحساب الجديد. من غير كده المالك القديم كان هيفضل يستقبل إشعارات
  الجهاز ده — تسريب حقيقي.
- `DELETE` بيمسح تسجيلك إنت بس؛ توكن حد تاني = بلا تأثير، وتوكن مجهول = 204.
- `/devices/status` بيفرّق بين «الإشعارات مقفولة على الجهاز» و«السيرفر أصلاً
  مافيهوش مزوّد Push مضبوط» — استخدمه في شاشة الإعدادات.

**الجدول:** `user_devices` (user_id, token unique, platform, locale, app_version,
last_seen_at).

**القناة:** `PushChannel` بتوصّل لكل أجهزة المستخدم وبتمسح التوكنات اللي المزوّد
بيقول عليها ميتة. الترانسبورت خلف واجهة `PushSender`:

| driver | السلوك |
|--------|--------|
| `log` (افتراضي) | بيسجّل الحمولة وبيبعتش. آمن في أي بيئة. |
| `fcm` | Firebase Cloud Messaging **HTTP v1** بـ OAuth2 من service account، والتوكن متكاش 55 دقيقة. |

الـ legacy server key بتاع FCM اتقفل 2024، عشان كده v1. التفعيل بـ env بس:
`PUSH_DRIVER=fcm` + `FCM_CREDENTIALS=/path/to/sa.json`. من غير الملف بيرجع لـ
`log` تلقائيًا.

**مابيرميش استثناء أبدًا** — بوّابة Push واقعة ماينفعش تفشّل المزايدة اللي
سبّبتها.

الإشعارات بتوصل الـ Push تلقائيًا من نفس نصوص الإشعار الداخلي، و`data.type`
بيحمل نفس مفردات BE-2 عشان الـ deep-link.

---

## 🟠 BE-16 · أكواد أخطاء ثابتة

**كان:** رسالة عربية بس.

**بقى:** `code` جنب الرسالة في كل رفض:

```jsonc
{ "message": "لقد اشتريت كراسة الشروط بالفعل.", "code": "already_bought_book" }
```

الأكواد المطلوبة كلها موجودة + 3 زيادة:

| الحالة | `code` | معناها للتطبيق |
|--------|--------|----------------|
| اشترى الكراسة بالفعل | `already_bought_book` | كمّل للتسجيل |
| الكراسة مجانية | `book_free` | كمّل للتسجيل |
| مسجّل بالفعل | `already_registered` | يقدر يزايد |
| لازم يشتري الكراسة | `must_purchase_book` | ارجع لخطوة الكراسة |
| غير مؤهّل | `not_eligible` | وجّه للتوثيق |
| سجل تجاري مطلوب | `commerce_register_required` | وجّه للسجل التجاري |
| مفيش مستحقات | `nothing_due` | — |
| مش الفائز | `not_winner` | *(جديد)* |
| الدفع النهائي تمّ | `final_already_paid` | *(جديد)* |
| خطأ بوّابة | `gateway_error` | *(جديد)* |

**التنفيذ:** `App\Exceptions\PaymentException` (بيورث `RuntimeException` عشان
الويب اللي بيمسك `RuntimeException` مايتغيّرش) بمصانع ساكنة، و`fail()` في
`RespondsWithEnvelope` بقى بياخد `?string $code`.

**اعتمد على `code` مش على النص.**

---

## 🟠 BE-3 · `GET /api/v1/my-auctions`

**بقى** كل عنصر معاه حالة المستخدم على المزاد:

```jsonc
{
  "id": "...", "title": "...",
  "my_highest_bid": { "amount": 12000, "formatted": "12 000 دج" },  // null لو ماعندوش
  "is_winning": true,        // مفتوح: صاحب أعلى عرض / مقفول: الفائز
  "is_winner": false,        // للمقفول بس
  "deposit_paid": true,
  "book_purchased": true,
  "registered_at": "2026-07-20T10:00:00+01:00",
  "final_payment_status": "PENDING"   // null لو مابدأش دفع نهائي
}
```

الحسابات دي بتتعمل **بالجملة** — 3 استعلامات ثابتة للصفحة كلها (أعلى عرض ليك،
أعلى عرض عمومًا، حالة الدفع النهائي)، مش استعلام لكل صف.

### السؤال بتاع التبويبات — متحلّ

التبويبات **مناظير** على نفس المجموعة مش تقسيم حصري — المزاد المكسوب مقفول كمان،
فهو في `won` و`lost` معايير مختلفة عليه. اتضاف تبويب **`all`** وهو الشامل:
المشاركة في مزاد اتلغى (`CANCELLED`) بتظهر فيه بس. `DRAFT` أصلاً مش ظاهر للمواطن
فمستحيل يوصل لمشاركة. `meta.counts` بقى فيه `all`.

```
GET /my-auctions?tab=all|active|won|lost|upcoming
```

---

## 🟠 BE-2 · `GET /api/v1/notifications`

**بقى** عمود `event` على جدول `notifications`، بيتكتب من `InAppChannel`
وبيترجع كـ `type`:

```jsonc
{
  "id": "...", "title": "...", "body": "...",
  "type": "outbid",        // ← جديد
  "channel": "IN_APP",
  "is_read": false,
  "action_url": "https://.../auctions/{id}",
  "created_at": "..."
}
```

- `type` = **إيه اللي حصل** → فرّع على ده.
- `channel` = **إزاي اتبعت** (IN_APP/EMAIL/PUSH).
- الصفوف الأقدم من المهاجرة `type: null` — اعمل fallback على العنوان.

**المفردات:** `outbid` · `auction_won` · `auction_lost` · `payment_confirmed` ·
`payment_failed` · `final_payment_due` · `deposit_refunded` · `deposit_forfeited` ·
`delivery_update` · `inspection_answered` · `condition_book_published` ·
`appeal_updated` · `kyc_approved` · `kyc_rejected` · `kyc_suspended` ·
`commercial_register_approved` · `commercial_register_rejected`

نفس المفردات بتوصل في `data.type` جوّه الـ Push وفي بثّ BE-10 — مفردة واحدة
للتوجيه في التلات مسارات.

---

## 🟠 BE-1 · `GET /api/v1/auctions`

**بقى** على `AuctionListResource` (يعني على `/auctions` و`/my-auctions`
و`dashboard.won_auctions` مرة واحدة):

```jsonc
"requires_commerce_register": true
```

---

## ⚠️ BE-12 · `GET /api/v1/reports/summary` — **تغيير كاسر**

**كان:** بيرجّع مصفوفة الخدمة الخام — **بالسنتيم**، بينما
`/reports/transactions` بالدينار. فرق ×100 بين شاشتين في نفس الصفحة.

**بقى:** كل مبلغ `{ amount, formatted }` **بالدينار**، زيّ باقي الـ API:

```jsonc
// قبل
{ "summary": { "net_revenue": 300000, "txn_count": 1 } }

// بعد
{
  "summary": {
    "net_revenue": { "amount": 3000, "formatted": "3 000 دج" },
    "txn_count": 1                       // العدّادات فضلت أرقام عادية
  },
  "by_type":   [{ "label": "...", "total": { "amount": 3000, "formatted": "..." }, "cnt": 1 }],
  "by_status": [{ "status": "CONFIRMED", "label": "...", "total": { ... }, "cnt": 1 }],
  "series":    { "categories": ["2026-07"], "data": [3000], "unit": "DZD" },
  "fees":      { "hammer_price": { ... }, "_count": 2 }
}
```

**لو التطبيق كان بيقسم على 100 يدويًا هنا — شيل القسمة.**

**اللي فضل أرقام عادية:** `txn_count` · `failed_count` · `cnt` · `fees._count`.
و`series.data` فضل مصفوفة أرقام (بالدينار) عشان يترسم على الشارت مباشرة —
`series.unit` بيوثّق ده صراحةً.

---

## 🟡 BE-8 · `GET /api/v1/profile`

**بقى** فيه كل حقول فورم الـ KYC للتعبئة المسبقة:

```
father_name · mother_name · mother_surname · rip · nif · nis
birth_date · birth_place · expected_income
id_card_number · passport_number · license_number
wilaya_id   ← مشتقّة من البلدية
```

`wilaya_id` مضافة لأن الفورم بيسأل عن الولاية الأول، والمستخدم مخزّن عنده
البلدية بس — فماتضطرش تعمل بحث عكسي في `/wilayas`.

نفس الحقول بترجع من `/auth/me` و`/auth/login` كمان (نفس الـ Resource).

---

## 🟡 BE-6 · `GET /api/v1/dashboard`

**بقى** على `AuctionListResource` — فـ `won_auctions` أخدهم تلقائيًا:

```jsonc
"final_price": { "amount": 25000, "formatted": "25 000 دج" },  // null قبل الإقفال
"closed_at": "2026-07-24T16:54:00+01:00"                        // null قبل الإقفال
```

---

## 🟡 BE-14 · `GET /api/v1/ping`

```jsonc
{
  "data": {
    "status": "ok",
    "version": "v1",
    "locale": "ar",
    "realtime": {
      "driver": "reverb",
      "key": "…", "host": "…", "port": 443, "scheme": "https",
      "auth_endpoint": "https://…/api/broadcasting/auth"
    }
  }
}
```

- بتتقري من بلوك `broadcasting.connections.reverb.client` — يعني الـ endpoint
  العام (wss)، مش الـ host الداخلي اللي السيرفر بينشر عليه.
- `realtime: null` لما البثّ مطفي → ارجع للـ polling على
  `/auctions/{id}/price` و`/auctions/{id}/bids`.
- `auth_endpoint` مضاف: القنوات الخاصة بتتصرّح من مسار الـ **API** بالتوكن، مش
  من `/broadcasting/auth` بتاع الويب.

> ⚠️ **الـ key العام بس.** `REVERB_APP_SECRET` مابيطلعش أبدًا — فيه تست بيتأكد
> إن النص مش موجود في الرد.

---

## 🟡 BE-4 · `GET /api/v1/documents/filters` — جديد

مقصور على مزادات المستخدم نفسه، وفيه `entities` اللي كانت ناقصة من
`/auctions/filters`:

```jsonc
{
  "data": {
    "categories": [{ "id": 1, "name": "مركبات" }],
    "wilayas":    [{ "id": 40, "code": "40", "name": "خنشلة" }],
    "entities":   [{ "id": "uuid", "name": "بلدية خنشلة" }],
    "types":      [{ "value": "AWARD", "label": "وثيقة الترسية" }],
    "presets":    ["today", "7d", "30d", "this_month", "this_year", "all"],
    "sorts":      ["recent", "oldest", "auction"]
  }
}
```

`AUCTION_REPORT` مستبعد (أدمن فقط). مستخدم من غير وثائق = مصفوفات فاضية.

---

## 🟡 BE-5 · إشعار قرار السجل التجاري

**تصحيح للطلب:** الصف الداخلي كان **موجود فعلاً** — `AdminCommercialRegisterController`
كان بيكتبه يدويًا، والمفاتيح `notif_approved_title/body` مستخدمة مش مهملة.
الناقص كان الـ Push و`type` بس.

**بقى:** توحيد — الصف اتنقل جوّه `CommercialRegisterStatusNotification::toInApp()`
و`via()` بقت `['mail', InAppChannel, PushChannel]`. نفس الشيء اتعمل لـ
`KycStatusNotification` (وكمان أمر `kyc:suspend-stale`).

المكسب: مصدر واحد للنص بدل تكرار في كل call site، و`event` على كل القنوات،
والـ Push جه مجانًا.

---

## 🟢 BE-7 · `GET /api/v1/reports/export/{format}`

`format` = `csv` أو `pdf`، بنفس فلاتر `/reports/summary`.

> **الرد ملف ثنائي، مش الـ envelope** — استقبله كـ bytes/stream واحفظه،
> ماتحاولش تعمله decode. ابعت التوكن عادي.

- **CSV**: بيتبعت chunked (500 صف) فتاريخ طويل مايستهلكش الذاكرة، وفيه UTF-8 BOM
  عشان Excel يقرا العربي صح.
- **PDF**: بيترندر بـ mpdf بنفس تشكيل/اتجاه العربي بتاع تصدير الويب.

الملاحظة في الطلب إن الـ PDF مش عملي — التصدير بيتبني من نفس التقرير المجمّع
اللي على الشاشة (KPIs + رسوم + جدول)، مش 500 صف خام، فهو عملي على الموبايل.

`format` غير معروف = 404.

---

## 🟢 BE-9 · `GET /api/v1/verify?doc=&sig=`

```jsonc
{
  "data": {
    "valid": true,
    "document": {
      "id": "...",
      "type": "AWARD", "type_label": "وثيقة الترسية",
      "title": "...", "issued_at": "...",
      "auction_title": "...", "entity_name": "...",
      "amount": 25000, "amount_formatted": "25 000 دج",
      "fingerprint": "A1B2C3"
    }
  }
}
```

> **وثيقة غير معروفة أو توقيع غلط → 200 مع `valid: false`، مش 404.**
> فشل التحقق هو **الإجابة**؛ كود خطأ كان هيبقى ملخبط مع مشكلة شبكة عند قارئ
> الـ QR.

الـ QR المطبوع على الوثيقة بيرمّز رابط الويب — خُد منه `doc` و`sig` ونادي
المسار ده. مافيش بيانات شخصية ولا الملف نفسه؛ التحميل لسه محتاج تصريح.

---

## 🟢 BE-10 · بثّ فعلي على `auction.{id}.user.{id}`

حدث `PersonalAuctionEvent`، اسم البثّ **`auction.personal`**:

```jsonc
{
  "type": "outbid",        // نفس مفردات BE-2
  "auction_id": "...",
  "timestamp": 1753900000,
  "new_price": 15000       // حسب النوع
}
```

**بيتبعت عند:** `outbid` · `auction_won` · `auction_lost` · `payment_confirmed` ·
`payment_failed`. المبالغ **بالدينار** زي الـ REST.

- `ShouldBroadcastNow` زي `BidPlaced` — بيوصل فورًا ومابيعتمدش على queue worker.
- **best-effort**: Reverb واقع مايفشّلش المزايدة/الدفع — الصف الداخلي والإيميل
  بيتبعتوا برضه، وإنت ترجع للـ polling.
- **مافيهوش بيانات شخصية** — اعمل re-fetch للمزاد أو اقرا `/notifications`
  للتفاصيل.
- التصريح من `POST /api/broadcasting/auth` بالتوكن. شرط القناة إنك مشارك مسجّل
  في المزاد — وده متحقّق أصلاً عشان تقدر تزايد.

القناة العامة `auction.{id}` بتقول «السعر اتحرك»؛ دي بتقول «عرضك إنت اللي
اتغلب» — الفرق ده كان مستحيل تعرفه قبل كده.

---

## الأسئلة المفتوحة — الإجابات

**بيانات Reverb المنشورة:** ماتاخدهاش مني ولا تعملها hardcode — هاتها من
`GET /ping → data.realtime` وقت التشغيل. `.env.example` بقى فيه
`REVERB_CLIENT_HOST` / `REVERB_CLIENT_PORT` / `REVERB_CLIENT_SCHEME` موثّقين،
وهيتضبطوا على سيرفر الإنتاج — فالتطبيق ياخدهم من غير إصدار جديد. الـ **secret**
مابيتنشرش.

**توحيد الـ ref:** اتحلّ من الناحيتين — `/status` بقى يقبل `gateway_ref` أو
`payment id`، وبيرجّع `gateway_ref` الرسمي عشان توحّد عليه.

**تبويبات `/my-auctions`:** اتضاف `all` الشامل، والتعريفات موثّقة في BE-3.

---

## قبل ما تبدأ التكامل

**لسه محتاج ضبط على السيرفر:**

1. **Firebase** — مشروع + service-account JSON → `FCM_CREDENTIALS` و
   `PUSH_DRIVER=fcm`. لغاية ما يتضبط، `/devices/status` هيرجّع
   `push_enabled: false` والتسجيل شغّال عادي (بس مافيش توصيل).
2. **Reverb الإنتاج** — `REVERB_CLIENT_*` على الدومين العام.

**مسارات لسه ماتجرّبتش على مزوّد حقيقي** (متغطّية بتستات بس التوصيل الفعلي
مجرّبش): إرسال Push عبر FCM، ورجوع Chargily الحقيقي من الـ WebView. أول ما
تجرّبهم، ابعت النتيجة.

---

## ملاحظة

التوثيق التفاعلي متولّد من جديد على **`/docs`** — فيه كل المسارات الجديدة
(`Devices` كمجموعة مستقلة)، وPostman collection وOpenAPI spec محدّثين.

التحقق:
```bash
php artisan test --filter=MobileClientContractTest   # 29 تست للبنود دي
php artisan test                                     # 337 تست، كلها خضرا
```
