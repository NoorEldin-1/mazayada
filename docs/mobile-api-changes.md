# تغييرات الـ API لتطبيق الموبايل — منفّذة

ردّ على `MOBILE_API_REQUESTS.md`. كل بند هنا **متحقق منه بتست**
(`tests/Feature/Api/V1/MobileClientContractTest.php`)، والتوثيق التفاعلي متولّد
من جديد على `/docs`.

الحالة: **16 / 16 بند منفّذ.** السؤال المفتوح عن Reverb متجاوَب معه (BE-14).

---

## ملخّص التنفيذ

| # | Endpoint | الحالة | ملاحظة سريعة |
|---|----------|--------|---------------|
| **BE-15** | `GET /auctions/{id}` | ✅ | `meta.viewer` بيتعبّى للمستخدم المسجّل |
| **BE-13** | بوّابات الدفع | ✅ | return URL خاص بالموبايل + `/status` بيقبل الاتنين |
| **BE-11** | `POST/DELETE /devices` | ✅ | جديد + قناة Push كاملة (FCM v1) |
| **BE-16** | مسارات الدفع | ✅ | `code` ثابت في كل رفض |
| **BE-3** | `GET /my-auctions` | ✅ | حالة مشاركة كاملة + تبويب `all` |
| **BE-2** | `GET /notifications` | ✅ | `type` ديناميّ |
| **BE-1** | `GET /auctions` | ✅ | `requires_commerce_register` |
| **BE-12** | `GET /reports/summary` | ✅ | كله بالدينار |
| **BE-8** | `GET /profile` | ✅ | حقول KYC للتعبئة المسبقة |
| **BE-6** | `GET /dashboard` | ✅ | `final_price` + `closed_at` |
| **BE-14** | `GET /ping` | ✅ | إعدادات Reverb العامة |
| **BE-4** | `GET /documents/filters` | ✅ | جديد |
| **BE-5** | إشعار السجل التجاري | ✅ | in-app + Push |
| **BE-7** | `GET /reports/export/{format}` | ✅ | جديد |
| **BE-9** | `GET /verify` | ✅ | نسخة JSON |
| **BE-10** | `auction.{id}.user.{id}` | ✅ | بثّ فعلي |

---

## ⚠️ تغييرات كاسرة (breaking) — لازم تتعامل معاها

اتنين بس، والباقي كله إضافات:

### 1. `GET /reports/summary` — المبالغ بقت بالدينار

كانت بترجع **سنتيم** خام. دلوقتي كل مبلغ بقى `{ amount, formatted }` بالدينار،
زيّ باقي الـ API. لو التطبيق كان بيقسم على 100 يدويًا هنا — شيل القسمة.

```jsonc
// قبل
{ "summary": { "net_revenue": 300000, "txn_count": 1 } }

// بعد
{
  "summary": {
    "net_revenue": { "amount": 3000, "formatted": "3 000 دج" },
    "txn_count": 1                      // العدّادات فضلت أرقام عادية
  },
  "series": { "categories": ["2026-07"], "data": [3000], "unit": "DZD" }
}
```
`series.data` فضل مصفوفة أرقام (بالدينار) عشان يترسم على الشارت مباشرة.
`failed_count` و `txn_count` و `fees._count` أرقام، مش مبالغ.

### 2. إشعارات KYC والسجل التجاري

الصف الداخلي (in-app) كان بيتكتب يدويًا من الأدمن كنترولر. اتنقل لقناة داخل
الـ Notification نفسها، فبقى معاه `event` و Push. **مافيش تغيير في شكل الرد** —
بس لو عندك تست بيعتمد على الترتيب/التوقيت، خد بالك.

---

## BE-15 · `GET /auctions/{id}` — `meta.viewer` بقى شغّال

**السبب الحقيقي** مش اللي في التقرير: الحارس `sanctum` **موجود** أصلاً (Sanctum
بيسجّله وقت التشغيل). المشكلة إن المسار عام من غير أي حارس، فـ `$request->user()`
بيرجع للحارس الافتراضي `web` (جلسة) — والموبايل مالوش جلسة.

الحل: middleware `token.optional` (`ResolveOptionalToken`) على مجموعة المزادات
العامة. بيقرأ التوكن لو موجود ويحطّ المستخدم على الريكوست، **ومابيرفضش أبدًا** —
التوكن الناقص أو الغلط بيسيب الطلب كضيف.

> **مهم:** ماضفناش `sanctum` لـ `config/auth.php` رغم إن التقرير اقترح كده.
> جربناها وكسرت كل فحوصات الأدوار (`There is no role named SUPER_ADMIN for guard
> sanctum`) في **الإنتاج** مش التستات بس — لأن `Authenticate` بينادي
> `shouldUse('sanctum')` مع كل طلب بتوكن، و spatie/permission بيدوّر على الأدوار
> تحت الحارس ده. التفصيلة متسجّلة كتعليق في `config/auth.php`.

الرد للمستخدم المسجّل بقى بالضبط زي المتوقّع في التقرير، و`data.has_book_access`
و`data.award_document` بقوا بيتعبّوا كمان (كانوا بيعتمدوا على نفس المستخدم).

**شروط قبول التوكن:** لازم يكون `access` (توكن الـ `refresh` مابيتقبلش كجلسة)
والحساب نشط. غير كده = ضيف.

---

## BE-13 · بوّابات الدفع

### return URL
`PaymentService` بقى بياخد `$channel` (`'web'` افتراضي / `'api'`)، وبيبني منه
`success_url` + `failure_url` ويمرّرهم للبوّابة. الكنترولرز بتاعة الـ API
بتبعت `'api'`، فالرجوع بيروح على:

```
GET /api/v1/payments/callback?ref={payment_id}&decision=success|fail
```

المسار ده عام (بدون جلسة)، idempotent، وبيتحقّق من البوّابة نفسها — فالـ query
string المزوّرة ماتقدرش تأكّد دفعة. **مش لازم تسيب الصفحة تفتح**: كفاية تكتشف
البادئة `/api/v1/payments/callback` في الـ WebView، تقفلها، وتعمل poll على
`/status`.

الويب فضل زي ما هو على `payments.callback` المحمي بالجلسة.

### `CibWebGateway`
بقى بيمرّر `ref` و `decision` (وكمان `failUrl`) بدل `returnUrl` فاضي.

### السؤال بتاع الـ ref — متحلّ
`GET /payments/{ref}/status` بقى بيقبل **الاتنين**: `gateway_ref` أو `payment id`.
وبيرجّع كمان:

```jsonc
{
  "ref": "<اللي بعتّه>",
  "gateway_ref": "<المرجع الرسمي>",   // جديد — عشان توحّد عليه
  "confirmed": true,                   // جديد — كل الصفوف مأكّدة؟
  "payments": [ ... ]
}
```
لسه محمي: دفعة مستخدم تاني بترجّع 404.

---

## BE-11 · `POST/DELETE /devices` — جديد

```
POST   /api/v1/devices   { token, platform: "ios"|"android", locale?, app_version? }  → 204
DELETE /api/v1/devices   { token }                                                     → 204
GET    /api/v1/devices/status                                                          → { push_enabled, devices }
```

- **idempotent**: ناديه كل مرة التطبيق يفتح وكل مرة التوكن يتجدّد.
- **التوكن هو الهوية**: لو نفس الجهاز سجّل دخول بحساب تاني، الصف بيتحوّل للحساب
  الجديد — فالمالك القديم بيبطّل يستقبل إشعارات الجهاز ده.
- `DELETE` بيمسح تسجيلك إنت بس (توكن حد تاني = بلا تأثير)، وتوكن مجهول برضه 204.
- `/devices/status` بيفرّق بين «الإشعارات مقفولة على الجهاز» و«السيرفر أصلاً
  مافيهوش مزوّد Push مضبوط».

### قناة الـ Push
`PushChannel` بتوصّل لكل أجهزة المستخدم، وبتمسح التوكنات اللي المزوّد بيقول
عليها ميتة. الترانسبورت خلف `PushSender`:

- `log` (الافتراضي) — بيسجّل الحمولة وبيبعتش. آمن في أي بيئة.
- `fcm` — Firebase Cloud Messaging **HTTP v1** (الـ legacy server key اتقفل 2024)،
  بـ OAuth2 من service account، والتوكن متكاش لـ 55 دقيقة.

التفعيل بـ env بس: `PUSH_DRIVER=fcm` + `FCM_CREDENTIALS=/path/to/sa.json`.
من غير الملف بيرجع لـ `log` تلقائيًا. **مابيرميش استثناء أبدًا** — بوّابة Push
واقعة ماينفعش تفشّل المزايدة اللي سبّبتها.

> ⚠️ باقي عليكم: إنشاء مشروع Firebase وتنزيل الـ service account JSON. الكود جاهز.

---

## BE-16 · أكواد أخطاء ثابتة

كل رفض في مسارات الدفع/التسجيل بقى معاه `code` جنب الرسالة:

```jsonc
{ "message": "لقد اشتريت كراسة الشروط بالفعل.", "code": "already_bought_book" }
```

الأكواد بالظبط زي ما طلبتوا: `already_bought_book` · `book_free` ·
`already_registered` · `must_purchase_book` · `not_eligible` ·
`commerce_register_required` · `nothing_due` — وزيادة: `not_winner` ·
`final_already_paid` · `gateway_error`.

التنفيذ: `App\Exceptions\PaymentException` (بيورث `RuntimeException` عشان الويب
مايتغيّرش) بمصانع ساكنة، و `fail()` في الـ envelope بقى بياخد `?string $code`.

**اعتمد على `code` مش على النص.**

---

## BE-3 · `GET /my-auctions`

كل عنصر بقى معاه حالة المستخدم على المزاد:

```jsonc
{
  "id": "...", "title": "...",
  "my_highest_bid": { "amount": 12000, "formatted": "12 000 دج" },  // null لو ماعندوش
  "is_winning": true,          // مفتوح: إنت صاحب أعلى عرض / مقفول: إنت الفائز
  "is_winner": false,          // للمقفول بس
  "deposit_paid": true,
  "book_purchased": true,
  "registered_at": "2026-07-20T10:00:00+01:00",
  "final_payment_status": "PENDING"   // null لو مابدأش دفع نهائي
}
```

الحسابات دي بتتعمل **بالجملة** (3 استعلامات ثابتة للصفحة كلها)، مش استعلام لكل صف.

### السؤال بتاع التبويبات — متحلّ
التبويبات **مناظير** على نفس المجموعة مش تقسيم حصري (المزاد المكسوب مقفول كمان).
ضفنا تبويب `all` وهو الشامل — المشاركة في مزاد اتلغى (`CANCELLED`) بتظهر فيه بس.
`DRAFT` أصلاً مش ظاهر للمواطن فمستحيل يوصل. `meta.counts` بقى فيه `all`.

---

## BE-2 · `type` على الإشعارات

عمود `event` جديد على جدول `notifications`، بيتكتب من `InAppChannel` وبيترجع
كـ `type`:

```jsonc
{ "type": "outbid", "channel": "IN_APP", ... }
```

- `type` = **إيه اللي حصل** (`outbid`, `auction_won`, `auction_lost`,
  `payment_confirmed`, `payment_failed`, `kyc_approved`, `kyc_rejected`,
  `kyc_suspended`, `commercial_register_approved`, `commercial_register_rejected`,
  `appeal_updated`, `delivery_update`, …) — **فرّع على ده**.
- `channel` = **إزاي اتبعت** (IN_APP/EMAIL/PUSH).
- الصفوف القديمة `type: null` — اعمل fallback على العنوان.

نفس المفردات بتوصل في `data.type` جوّه إشعار الـ Push وفي بثّ BE-10.

---

## BE-1 / BE-6 · حقول جديدة على `AuctionListResource`

اتضافت لكل القوائم (`/auctions`, `/my-auctions`, `dashboard.won_auctions`):

```jsonc
"requires_commerce_register": true,
"final_price": { "amount": 25000, "formatted": "25 000 دج" },  // null قبل الإقفال
"closed_at": "2026-07-24T16:54:00+01:00"                        // null قبل الإقفال
```

BE-6 اتحلّ تلقائيًا بده — `won_auctions` بيستخدم نفس الـ resource.

---

## BE-8 · حقول KYC في `GET /profile`

اتضافت: `father_name`, `mother_name`, `mother_surname`, `rip`, `nif`, `nis`,
`birth_date`, `birth_place`, `expected_income`, `id_card_number`,
`passport_number`, `license_number`، وكمان **`wilaya_id`** (مشتقّة من البلدية،
لأن الفورم بيسأل عن الولاية الأول والمستخدم مخزّن عنده البلدية بس).

نفس الحقول بترجع من `/auth/me` و`/auth/login` كمان.

---

## BE-14 · إعدادات Reverb في `GET /ping`

```jsonc
{
  "data": {
    "status": "ok", "version": "v1", "locale": "ar",
    "realtime": {
      "driver": "reverb",
      "key": "...", "host": "...", "port": 443, "scheme": "https",
      "auth_endpoint": "https://.../api/broadcasting/auth"
    }
  }
}
```

- بتتقري من بلوك `client` (الـ endpoint العام wss)، مش الـ host الداخلي اللي
  السيرفر بينشر عليه.
- `realtime: null` لما البثّ مطفي → ارجع للـ polling.
- `auth_endpoint` مضاف عشان القنوات الخاصة بتتصرّح من مسار الـ API بالتوكن، مش
  من `/broadcasting/auth` بتاع الويب.
- **الـ SECRET مابيطلعش أبدًا** (فيه تست بيتأكد إن النص مش موجود في الرد).

### السؤال المفتوح — الإجابة
مش محتاجين تاخدوا القيم مني: هاتوها من `/ping` وقت التشغيل. `.env.example` بقى
فيه `REVERB_CLIENT_HOST/PORT/SCHEME` موثّقين. اضبطوهم على سيرفر الإنتاج والتطبيق
هياخدهم من غير إصدار جديد.

---

## BE-4 · `GET /documents/filters` — جديد

مقصور على مزادات المستخدم نفسه، وفيه `entities` اللي كانت ناقصة:

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

## BE-5 · إشعار السجل التجاري

**تصحيح للتقرير:** الصف الداخلي كان **موجود فعلاً** — الأدمن كنترولر كان بيكتبه
يدويًا (`AdminCommercialRegisterController`)، والمفاتيح العربية كانت مستخدمة مش
مهملة. الناقص كان الـ Push و`type`.

اتعمل توحيد: الصف اتنقل جوّه `CommercialRegisterStatusNotification::toInApp()`،
و`via()` بقت `['mail', InAppChannel, PushChannel]`. نفس الشيء اتعمل لـ
`KycStatusNotification`. دلوقتي مصدر واحد للنص، و`event` على كل القنوات.

---

## BE-7 · `GET /reports/export/{format}` — جديد

`format` = `csv` أو `pdf`، بنفس فلاتر `/reports/summary`.

**الرد ملف ثنائي، مش الـ envelope** — استقبله كـ bytes/stream واحفظه، ماتحاولش
تعمله decode. الـ CSV بيتبعت chunked (500 صف) فتاريخ طويل آمن، وفيه BOM عشان
Excel يقرا العربي.

---

## BE-9 · `GET /verify?doc=&sig=` — نسخة JSON

```jsonc
{ "data": { "valid": true, "document": {
    "id": "...", "type": "AWARD", "type_label": "وثيقة الترسية",
    "title": "...", "issued_at": "...",
    "auction_title": "...", "entity_name": "...",
    "amount": 25000, "amount_formatted": "25 000 دج",
    "fingerprint": "A1B2C3"
} } }
```

وثيقة غير معروفة أو توقيع غلط → **200** مع `valid: false` (مش 404): فشل التحقق
هو **الإجابة**، وكود خطأ كان هيبقى ملخبط مع مشكلة شبكة عند قارئ الـ QR.
مافيش بيانات شخصية ولا الملف نفسه — التحميل لسه محتاج تصريح.

---

## BE-10 · بثّ فعلي على `auction.{id}.user.{id}`

حدث جديد `PersonalAuctionEvent` (اسم البثّ `auction.personal`):

```jsonc
{
  "type": "outbid",              // نفس مفردات BE-2
  "auction_id": "...",
  "timestamp": 1753900000,
  "new_price": 15000             // حسب النوع
}
```

بيتبعت عند: `outbid` · `auction_won` · `auction_lost` · `payment_confirmed` ·
`payment_failed`. المبالغ **بالدينار** زي الـ REST.

- best-effort: Reverb واقع مايفشّلش المزايدة/الدفع — الصف الداخلي والإيميل
  بيتبعتوا برضه، وإنت ترجع للـ polling.
- التصريح للقناة من `POST /api/broadcasting/auth` بالتوكن (شرطه إنك مشارك
  مسجّل في المزاد — وده متحقّق أصلاً عشان تقدر تزايد).

---

## اللي محتاج منكم قبل الإنتاج

1. **Firebase**: إنشاء المشروع + تنزيل service-account JSON → `FCM_CREDENTIALS`،
   و`PUSH_DRIVER=fcm`. الملف **بره** الـ document root ومايتعملّوش commit.
2. **Reverb الإنتاج**: ضبط `REVERB_CLIENT_HOST/PORT/SCHEME` على الدومين العام.
3. **Migrations**: `php artisan migrate` (جدولين: `user_devices` + عمود `event`).

## التحقق

```bash
php artisan test --filter=MobileClientContractTest   # 26 تست للبنود دي
php artisan test                                     # 334 تست، كلها خضرا
php artisan scribe:generate                          # /docs متولّد من جديد
```
