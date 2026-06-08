# Tasks & Workers Modules Implementation Plan

## الهدف من المرحلة

تجهيز خطة تنفيذ واضحة لموديول **Tasks** وموديول **Workers** بدون كتابة كود في هذه المرحلة. الخطة مبنية على المتطلبات الحالية:

- كل شركة تقدر تنشئ Tasks خاصة بها.
- كل Task يحتوي على أكثر من Service.
- كل Service داخل الـ Task ترتبط بأكثر من Product من منتجات الشركة.
- الشركة تضيف تفاصيل مختلفة لكل Service ولكل Product أثناء إنشاء الـ Task.
- لكل Service حد أدنى للسعر ووقت تنفيذ أدنى، والشركة يمكنها الزيادة عليهما فقط ولا يمكنها تقليلهم.
- إجمالي تكلفة الـ Task = مجموع أسعار كل Services المختارة بعد الزيادات المسموحة.
- يتم حجز/خصم مبلغ الـ Task مؤقتًا من Wallet الشركة، وبعدها يصبح الـ Task في حالة Active.
- كل Service يمكن أن يكون لها Requirements/Form مختلفة عن باقي الخدمات.
- الـ Task يرتبط بموقع التنفيذ: latitude، longitude، اسم المكان، والعنوان/الوصف.
- الـ Worker يستطيع التسجيل أو يتم إضافته من Shelf Spot، ثم يرسل موقعه الحالي، وبناءً عليه تظهر له الـ Tasks القريبة مع إمكانية توسيع دائرة البحث.

## الوضع الحالي في المشروع

يوجد بالفعل أساس مبدئي يمكن البناء عليه:

- جدول `services` يحتوي على `minimum_price` و `minimum_execution_time`.
- جدول `tasks` يحتوي على بيانات الشركة، التاريخ، الوقت، الموقع، الأسعار، الحالة، العامل المعيّن، وحالة الدفع.
- جدول `task_services` يربط الـ Task بالـ Services ويحفظ السعر والتعليمات و `request_details`.
- جدول `task_service_products` يربط كل Task Service بالمنتجات ويحفظ `product_details`.
- جدول `workers` يحتوي على موقع العامل الحالي `last_latitude` و `last_longitude` ووقت تحديث الموقع.
- يوجد Use Cases مبدئية لخصم/استرجاع Wallet الـ Task.

> تم تثبيت تفاصيل Requirements لكل Service في هذه النسخة بناءً على تفاصيل الخدمات المرسلة، وسيتم استخدامها لاحقًا كـ JSON Schema/validation rules لكل Service.

## مبادئ التنفيذ المتفق عليها

1. **عدم كسر الحد الأدنى للخدمة**
   - سعر الـ Service داخل الـ Task لا يقل عن `services.minimum_price`.
   - وقت التنفيذ داخل الـ Task لا يقل عن `services.minimum_execution_time`.

2. **عزل بيانات الشركة**
   - الشركة ترى وتستخدم منتجاتها فقط عند إنشاء Task.
   - أي Product داخل Task Service يجب أن يكون تابعًا لنفس الشركة.

3. **Wallet Ledger هو مصدر الحقيقة**
   - خصم تكلفة الـ Task يتم من خلال ledger transaction.
   - عند فشل إنشاء الـ Task أو عدم اكتماله يجب ألا يبقى حجز مالي غير صحيح.

4. **إنشاء الـ Task عملية Atomic**
   - إنشاء Task + Task Services + Products + خصم Wallet يجب أن يتم داخل Database Transaction واحدة.

5. **Worker Discovery منفصل عن Assignment**
   - ظهور Tasks قريبة للـ Worker لا يعني أنها assigned له.
   - الـ Assignment يحدث فقط عند accept/claim حسب flow يتم تثبيته لاحقًا.

6. **التوسع الجغرافي تدريجي**
   - الـ Worker يبدأ بنطاق قريب افتراضي.
   - يمكنه زيادة radius للحصول على Tasks أبعد.

## Service Catalog & Requirements Matrix

هذه هي الخدمات التي سيتم بناؤها في المرحلة الأولى، مع فصل واضح بين **Company Request Form** عند إنشاء الـ Task و **Worker Submission Form** عند التنفيذ.

### 1. Home-shelf / Primary Display

#### وصف الخدمة

التأكد أن المنتجات معروضة على الرف الأساسي حسب execution guidelines:

- تطبيق Share of Shelf الصحيح كما هو موجود في الـ Planogram.
- تطبيق FIFO.
- تطبيق Pricing Tag سواء Shelf boy و/أو Backdoor.

#### Company Request Form

- Date.
- Service Type.
- Description / execution instructions.
- Location.
- Brand.
- Range / Sub-brand.
- Category.
- Sub-category.
- Upload Planogram.
- Products/SKUs المرتبطة بالخدمة من كتالوج الشركة.

#### Worker Submission Form

- عرض معلومات الـ Job Order للعامل: location, service type, brand, sub-brand, category, sub-category, and attached planogram/job order.
- Before Picture.
- After Picture.
- Additional notes.

### 2. Secondary Display Execution

#### وصف الخدمة

التأكد أن المنتجات معروضة في المكان الصحيح داخل المتجر بناءً على Job Order، مع تطبيق Secondary Display Planogram/Guidelines.

#### Company Request Form

- Date.
- Service Type.
- Description / execution instructions.
- Location.
- Brand.
- Range / Sub-brand.
- Category.
- Sub-category.
- Upload Planogram and/or Upload Job Order.
- Products/SKUs المرتبطة بالخدمة من كتالوج الشركة.

#### Worker Submission Form

- عرض معلومات الـ Job Order للعامل: location, service type, brand, sub-brand, category, sub-category, and attached planogram/job order.
- Before Picture.
- After Picture.
- Additional notes.

### 3. On-shelf Availability

#### وصف الخدمة

تقرير حالة توفر كل منتج على الرف، والقيمة تكون إما **Available** أو **Unavailable**.

#### Company Request Form

- Date.
- Service Type.
- Description / execution instructions.
- Location.
- Brand.
- Range / Sub-brand.
- Category.
- Sub-category.
- Upload Planogram.
- Products/SKUs التي سيتم فحص توفرها.

#### Worker Submission Form

- عرض معلومات الـ Job Order للعامل: location, service type, brand, sub-brand, category, sub-category, and attached planogram/job order.
- جدول لكل SKU مطلوب من Client Database بناءً على اختيار الشركة:
  - SKU.
  - Availability status: `available` أو `unavailable`.
- Additional notes إذا احتاج العامل يضيف ملاحظات.

### 4. Instore Visibility / Taking Pictures

#### وصف الخدمة

تصوير المنتجات في Primary Display و/أو Secondary Display لإثبات الظهور داخل المتجر.

#### Company Request Form

- Date.
- Service Type.
- Description / execution instructions.
- Location.
- Brand.
- Range / Sub-brand.
- Category.
- Sub-category.
- Upload Planogram.
- Products/SKUs المراد تصويرها أو إثبات ظهورها.

#### Worker Submission Form

- عرض معلومات الـ Job Order للعامل: location, service type, brand, sub-brand, category, sub-category, and attached planogram/job order.
- Uploading pictures.
- Additional notes.

### 5. Freshness Report / Must Go Backdoor

#### وصف الخدمة

تقرير تواريخ انتهاء صلاحية المنتجات مع الكميات، وغالبًا يحتاج العامل الرجوع للـ Backdoor.

#### Company Request Form

- Date.
- Service Type.
- Description / execution instructions.
- Location.
- Brand.
- Range / Sub-brand.
- Category.
- Sub-category.
- Upload Planogram.
- Products/SKUs المطلوب عمل freshness report لها.
- Quantity إذا الشركة تريد تحديد كمية مستهدفة/متوقعة.
- Expiry Date إذا الشركة تريد إدخال تاريخ مبدئي أو expected expiry date.

#### Worker Submission Form

- عرض معلومات الـ Job Order للعامل: location, service type, brand, sub-brand, category, sub-category, and attached planogram/job order.
- جدول لكل SKU:
  - SKU.
  - Quantity.
  - Expiry Date.
- Additional notes إذا احتاج العامل يضيف ملاحظات.

## Service Form Storage Strategy

### Company-side request details and files

إنشاء الـ Task سيكون `multipart/form-data` على نفس endpoint، وليس flow منفصل لرفع الملفات. كل Service داخل request تقدر ترفع ملفاتها الخاصة مثل Planogram أو Job Order مع باقي بياناتها.

سيتم حفظ البيانات المتغيرة غير الملفاتية لكل Service في `task_services.request_details` كـ JSON، مع الاحتفاظ بالـ Products المختارة في `task_service_products`. أما الملفات نفسها فسيتم استقبالها من نفس request، ثم تخزينها كـ media records وربطها بالـ Task Service أثناء نفس transaction/flow.

مثال structure للجزء الـ JSON داخل `payload` في multipart request:

```json
{
  "brand_id": 1,
  "sub_brand_id": 2,
  "category_id": 3,
  "sub_category_id": 4,
  "description": "Execution instructions from company",
  "freshness_expected": {
    "quantity": 200,
    "expiry_date": "2026-10-20"
  }
}
```

وملفات نفس الـ Service تترفع في نفس request تحت keys متفق عليها مثل:

```txt
services[0][request_files][planogram_files][]=<file>
services[0][request_files][job_order_files][]=<file>
```

### Product-level details

سيتم حفظ التفاصيل الخاصة بكل Product/SKU في `task_service_products.product_details` حتى نقدر ندعم حالات مثل availability أو freshness بدون تغيير schema في كل مرة.

مثال:

```json
{
  "requested_quantity": 5,
  "notes": "Focus on this SKU during execution"
}
```

### Worker-side submission details

سيتم حفظ بيانات تنفيذ العامل في `task_service_submissions.form_data` كـ JSON مختلف حسب نوع الخدمة.

أمثلة:

```json
{
  "additional_notes": "FIFO applied and price tags updated"
}
```

وملفات التنفيذ تترفع في نفس submission request، مثل:

```txt
before_picture_files[]=<file>
after_picture_files[]=<file>
```

```json
{
  "items": [
    {
      "sku": "123",
      "availability": "available"
    },
    {
      "sku": "456",
      "availability": "unavailable"
    }
  ],
  "additional_notes": "SKU 456 was not found on shelf"
}
```

```json
{
  "items": [
    {
      "sku": "123",
      "quantity": 200,
      "expiry_date": "2026-10-20"
    },
    {
      "sku": "456",
      "quantity": 16,
      "expiry_date": "2026-10-10"
    }
  ]
}
```

### Attachment rules

- Planogram مطلوب لكل الخدمات حسب التفاصيل المرسلة.
- Job Order مطلوب/اختياري في Secondary Display Execution حسب اختيار الشركة، لكنه يجب أن يكون مدعومًا كنوع attachment منفصل.
- الملفات تترفع داخل نفس request الخاص بإنشاء الـ Task أو Worker submission، وليس endpoint منفصل.
- بعد استلام request، السيرفر يخزن الملفات كـ Media records ويربطها بالـ Task Service أو submission.
- لا يتم حفظ raw file paths داخل JSON؛ يتم حفظ بيانات business في JSON، والملفات تُدار من خلال Media records مرتبطة بالكيان المناسب.

## Phase 0: تثبيت المتطلبات وقرارات المنتج

### الهدف

إغلاق الأسئلة المفتوحة قبل التنفيذ حتى لا نضطر لتغيير DB أو contracts لاحقًا.

### المطلوب

1. تثبيت قائمة Services المتاحة ومفاتيحها `service.key` بناءً على Service Catalog أعلاه:
   - `home_shelf_primary_display`.
   - `secondary_display_execution`.
   - `on_shelf_availability`.
   - `instore_visibility`.
   - `freshness_report`.
2. تحويل Requirements/Form لكل Service إلى validation schema:
   - Company Request Form لكل Service.
   - Worker Submission Form لكل Service.
   - file fields مثل `planogram_files`, `job_order_files`, `before_picture_files`, `after_picture_files`, `picture_files`.
   - product-level fields مثل quantity, expiry date, availability.
3. تحديد حالات الـ Task النهائية:
   - `draft`
   - `active`
   - `accepted`
   - `in_progress`
   - `completed`
   - `cancelled`
   - `expired`
   - `declined`
4. تحديد حالات الدفع:
   - `pending`
   - `charged`
   - `refunded`
   - `failed`
5. تحديد قواعد انتهاء صلاحية الـ Task:
   - هل لها `expires_at` تلقائي؟
   - ماذا يحدث لو لم يقبلها Worker؟
6. تحديد قواعد الإلغاء والاسترجاع:
   - إلغاء قبل قبول Worker.
   - إلغاء بعد قبول Worker.
   - إلغاء بعد بدء التنفيذ.
7. تحديد radius الافتراضي للـ Workers، والحد الأقصى المسموح.

### Acceptance Criteria

- Service Catalog أعلاه يتحول إلى seeds/config واضحة للخدمات الخمسة.
- Requirements لكل Service تتحول إلى validation rules قابلة للاختبار.
- توجد State Machine متفق عليها للـ Task والـ Payment.
- توجد قواعد واضحة للـ Wallet hold/refund.

## Phase 1: Task Domain & Data Contract

### الهدف

تجهيز شكل البيانات النهائي للـ Task قبل APIs.

### المطلوب

1. مراجعة الجداول الحالية والتأكد هل تحتاج تعديلات:
   - إضافة `minimum_execution_time` أو `duration_minutes` على `task_services` لو الوقت لكل Service سيختلف داخل الـ Task.
   - إضافة `total_duration_minutes` أو حسابه من مجموع Services.
   - تحديد هل `unit_price` هو السعر النهائي للـ Service داخل الـ Task أم يحتاج تسمية أوضح مثل `price`.
2. تثبيت شكل `request_details` داخل `task_services`.
3. تثبيت شكل `product_details` داخل `task_service_products`.
4. تحديد العلاقات داخل Models:
   - Task belongs to Company.
   - Task has many TaskServices.
   - TaskService belongs to Service.
   - TaskService has many TaskServiceProducts.
   - TaskServiceProduct belongs to Product.
5. إنشاء DTO/Value Object أو Form Contract لتوحيد payload إنشاء الـ Task.

### Payload مقترح لإنشاء Task

الـ endpoint يستقبل `multipart/form-data`. يمكن إرسال البيانات المتداخلة كـ `payload` JSON، ومعها ملفات كل Service في نفس request. المثال التالي يوضح الـ payload المنطقي، مع ملاحظة أن `<file>` يمثل ملف مرفوع في multipart وليس string داخل JSON.

```json
{
  "date": "2026-06-20",
  "execution_time": "14:00",
  "location": {
    "latitude": 30.0444,
    "longitude": 31.2357,
    "location_name": "فرع مدينة نصر",
    "address": "العنوان التفصيلي"
  },
  "notes": "ملاحظات عامة على التاسك",
  "services": [
    {
      "service_id": 1,
      "price": 150.00,
      "execution_time_minutes": 90,
      "execution_instructions": "تعليمات تنفيذ الخدمة",
      "request_details": {
        "brand_id": 1,
        "sub_brand_id": 2,
        "category_id": 3,
        "sub_category_id": 4
      },
      "request_files": {
        "planogram_files": ["<file>"]
      },
      "products": [
        {
          "product_id": 10,
          "product_details": {
            "requested_quantity": 5,
            "notes": "تفاصيل خاصة بالمنتج داخل الخدمة"
          }
        }
      ]
    }
  ]
}
```

### Acceptance Criteria

- يوجد contract واضح لإنشاء Task.
- يمكن حساب تكلفة ومدة الـ Task من services payload.
- لا يوجد ambiguity بين تفاصيل الـ Service وتفاصيل الـ Product.

## Phase 2: Company Task Creation Flow

### الهدف

تمكين الشركة من إنشاء Task كامل وربطه بالخدمات والمنتجات وحجز قيمته من المحفظة.

### المطلوب

1. إنشاء/تجهيز endpoint للشركة:
   - `POST /v1/company/tasks`
   - `GET /v1/company/tasks`
   - `GET /v1/company/tasks/{id}`
2. Validation لإنشاء الـ Task:
   - الشركة authenticated و active.
   - وجود location صحيح.
   - وجود Service واحدة على الأقل.
   - كل Service active.
   - السعر لا يقل عن الحد الأدنى.
   - وقت التنفيذ لا يقل عن الحد الأدنى.
   - كل Product تابع للشركة الحالية.
   - كل Service يحتوي على Product واحد على الأقل إذا كان ذلك مطلوبًا حسب نوع الخدمة.
   - `request_details` يطابق Company Request Form الخاص بالـ Service.
   - الملفات المطلوبة مرفوعة في نفس request حسب نوع الخدمة، خصوصًا Planogram، و Job Order في Secondary Display عند الحاجة.
3. Pricing Service:
   - يجمع أسعار Services.
   - يتحقق من الحد الأدنى لكل Service.
   - يحسب `subtotal` و `total_price`.
4. Wallet Charging:
   - التحقق من كفاية رصيد الشركة.
   - إنشاء Wallet transaction من نوع task payment/debit.
   - تحديث `payment_status` إلى `charged`.
5. تفعيل الـ Task:
   - بعد نجاح الخصم يتم تحويل status إلى `active`.
   - ضبط `expires_at` حسب قاعدة المنتج المتفق عليها.
6. Error Handling:
   - إذا فشل أي جزء داخل transaction، لا يتم إنشاء Task ناقص ولا خصم مبلغ.

### Acceptance Criteria

- الشركة تستطيع إنشاء Task يحتوي على عدة Services وعدة Products لكل Service.
- لا يمكن إنشاء Task بسعر Service أقل من الحد الأدنى.
- لا يمكن استخدام Product لا يخص الشركة الحالية.
- لا يصبح الـ Task `active` إلا بعد نجاح خصم/حجز المبلغ من Wallet الشركة.
- يتم إرجاع تفاصيل السعر النهائي للـ Task في response.

## Phase 3: Company Task Management

### الهدف

إتاحة متابعة وإدارة Tasks من جهة الشركة بعد الإنشاء.

### المطلوب

1. Listing مع filters:
   - status.
   - date range.
   - assigned worker.
   - location/search إذا مطلوب.
2. Show Task details:
   - بيانات Task.
   - Services.
   - Products.
   - Worker assigned إن وجد.
   - Payment status.
   - Status history.
3. Cancellation Flow:
   - إتاحة إلغاء Task حسب القواعد.
   - استدعاء refund use case إذا كان الإلغاء يستحق استرجاع.
4. Status History:
   - تسجيل أي انتقال status في `task_status_histories`.
5. حماية transitions:
   - لا يمكن إلغاء Task مكتمل.
   - لا يمكن تعديل Task بعد قبوله إلا حسب قواعد متفق عليها.

### Acceptance Criteria

- الشركة ترى Tasks الخاصة بها فقط.
- الشركة ترى تفاصيل كل Service/Product داخل Task.
- إلغاء الـ Task يطبق قواعد refund بشكل صحيح.
- كل تغيير status يتم تسجيله.

## Phase 4: Worker Authentication & Admin Creation

### الهدف

تجهيز دخول العمال للنظام سواء بالتسجيل الذاتي أو إضافة من Shelf Spot Admin.

### المطلوب

1. Worker self sign up:
   - endpoint register للـ Worker إذا كان غير مفعل حاليًا.
   - phone/email verification حسب authentication flow الحالي.
   - إنشاء User + Worker profile.
2. Admin creates Worker:
   - `POST /v1/admin/workers`
   - بيانات العامل الأساسية.
   - تفعيل/تعطيل العامل.
3. Worker profile endpoints:
   - `GET /v1/worker/profile`
   - `PATCH /v1/worker/profile`
4. Activation Rules:
   - العامل غير active لا يستطيع رؤية أو قبول Tasks.

### Acceptance Criteria

- Worker يمكنه الدخول للنظام بتوكن خاص بـ worker portal.
- Shelf Spot Admin يمكنه إضافة Worker.
- العامل غير المفعل يتم منعه من الـ operational endpoints.

## Phase 5: Worker Location Session

### الهدف

حفظ آخر موقع للعامل واستخدامه كأساس لعرض الـ Tasks القريبة.

### المطلوب

1. Endpoint لتحديث موقع العامل:
   - `POST /v1/worker/location`
2. البيانات المطلوبة:
   - latitude.
   - longitude.
   - optional accuracy.
   - optional device timestamp.
3. حفظ الموقع في worker profile:
   - `last_latitude`
   - `last_longitude`
   - `location_updated_at`
4. Session/Cache Strategy:
   - استخدام DB كمصدر دائم للموقع الحالي.
   - يمكن إضافة cache لاحقًا لتحسين الأداء.
5. Security/Validation:
   - latitude/longitude ranges.
   - throttling لمنع spam.
   - لا يمكن لعامل تحديث موقع عامل آخر.

### Acceptance Criteria

- Worker يستطيع إرسال موقعه الحالي.
- النظام يحفظ آخر موقع وتاريخ تحديثه.
- لا تظهر Tasks للعامل إذا لم يكن لديه موقع حديث حسب threshold يتم تحديده.

## Phase 6: Nearby Active Tasks Discovery

### الهدف

إظهار الـ Tasks القريبة من العامل بناءً على موقعه الحالي مع إمكانية توسيع radius.

### المطلوب

1. Endpoint:
   - `GET /v1/worker/tasks/nearby?radius_km=5`
2. Query rules:
   - Tasks status = `active`.
   - payment_status = `charged`.
   - ليس لها assigned_worker_id.
   - لم تنته صلاحيتها.
   - داخل radius المطلوب من آخر موقع للعامل.
3. Sorting:
   - الأقرب أولًا.
   - ثم وقت التنفيذ/التاريخ.
4. Radius controls:
   - default radius مثل 5 km.
   - max radius مثل 50 km أو حسب قرار المنتج.
   - العامل يقدر يوسع النطاق تدريجيًا.
5. Distance Calculation:
   - في البداية يمكن استخدام Haversine SQL calculation.
   - لاحقًا يمكن استخدام spatial indexes لو الأداء احتاج.
6. Response:
   - task id.
   - distance_km.
   - date/time.
   - location_name بدون كشف بيانات حساسة غير مطلوبة.
   - total_price.
   - services summary.
   - estimated duration.

### Acceptance Criteria

- Worker يرى فقط Tasks القريبة والمتاحة.
- Worker لا يرى Tasks assigned لعامل آخر.
- زيادة radius تعيد نتائج أكثر حسب الموقع.
- النتائج مرتبة حسب المسافة.

## Phase 7: Worker Task Acceptance & Execution Flow

### الهدف

تجهيز flow قبول وتنفيذ الـ Task بعد ظهورها للعامل.

### المطلوب

1. Accept/Claim endpoint:
   - `POST /v1/worker/tasks/{task}/accept`
2. Concurrency Control:
   - lock على task row أثناء القبول.
   - لا يمكن لعاملين قبول نفس الـ Task.
3. Status transitions:
   - active -> accepted.
   - accepted -> in_progress.
   - in_progress -> completed.
4. Worker service submissions:
   - استخدام `task_service_submissions` لإرسال نتائج كل Service.
   - validation حسب Worker Submission Form الخاصة بكل Service.
   - Home-shelf و Secondary Display يحتاجان before/after pictures.
   - Instore Visibility يحتاج `picture_files`.
   - On-shelf Availability يحتاج availability لكل SKU: available/unavailable.
   - Freshness Report يحتاج quantity و expiry date لكل SKU.
5. Completion rules:
   - لا يكتمل Task إلا بعد اكتمال كل required service submissions.
6. Worker rejection/decline:
   - إذا العامل رفض بعد acceptance، يتم تحديد هل يعود Task إلى active أم يصبح declined/cancelled حسب قرار المنتج.

### Acceptance Criteria

- لا يمكن لأكثر من Worker قبول نفس Task.
- Task ينتقل بين الحالات بشكل مضبوط.
- Worker يستطيع إرسال بيانات التنفيذ لكل Service.
- لا يتم إكمال Task بدون submissions المطلوبة.

## Phase 8: Wallet Settlement & Worker Earnings

### الهدف

تحديد ما يحدث للأموال بعد انتهاء الـ Task.

### المطلوب

1. تثبيت قاعدة settlement:
   - هل المبلغ المحجوز يتحول بالكامل إلى Worker wallet؟
   - هل توجد عمولة Shelf Spot؟
   - متى يتم التحويل: عند complete مباشرة أم بعد approval؟
2. إنشاء Worker wallet transaction عند الاستحقاق.
3. تحديث Company wallet transaction/reference إذا مطلوب.
4. Refund جزئي أو كامل عند فشل Task.
5. Withdrawal لاحقًا من Worker wallet خارج نطاق المرحلة الأولى إذا لم يكن مطلوبًا الآن.

### Acceptance Criteria

- الأموال المحجوزة للـ Task لها مصير واضح.
- عند اكتمال Task يتم تسجيل أرباح Worker بشكل ledger واضح.
- عند فشل/إلغاء Task يتم تطبيق refund rule.

## Phase 9: Admin Monitoring & Operations

### الهدف

إعطاء Shelf Spot رؤية وتحكم تشغيلي في Tasks وWorkers.

### المطلوب

1. Admin list/show tasks.
2. Filters حسب:
   - company.
   - worker.
   - status.
   - date range.
   - city/location إن وجد.
3. Manual assignment أو reassignment إذا مطلوب.
4. إدارة Workers:
   - list/show/update.
   - activate/deactivate.
   - رؤية آخر موقع للعامل.
5. Operational audit/status history.

### Acceptance Criteria

- Admin يستطيع متابعة Tasks وWorkers.
- Admin يستطيع تعطيل Worker ومنعه من قبول Tasks.
- Admin يستطيع رؤية حالة كل Task ومراحلها.

## Phase 10: Testing Plan

### Unit Tests

- Task pricing calculation.
- Minimum service price validation.
- Minimum execution time validation.
- Company request form validation per service.
- Worker submission form validation per service.
- Distance calculation.
- Valid status transitions.

### Feature Tests

- Company creates Task successfully.
- Company cannot create Task with insufficient wallet balance.
- Company cannot use another company product.
- Company cannot reduce service price/time below minimum.
- Task creation rollback on wallet charge failure.
- Worker updates location.
- Worker lists nearby active Tasks.
- Worker expands radius and gets additional Tasks.
- Worker accepts Task with concurrency protection.
- Worker completes submissions.
- Company cancellation triggers refund when eligible.

### Integration Tests

- Full flow: Company wallet recharge -> create Task -> worker location -> nearby tasks -> accept -> complete -> settlement.

## Suggested Delivery Milestones

### Milestone 1: Planning & Contracts

- Final Requirements per Service.
- Final Task status/payment state machine.
- Final API contracts.

### Milestone 2: Company Task Creation

- Create/list/show company Tasks.
- Pricing + validation.
- Wallet charge + active status.

### Milestone 3: Worker Foundation

- Worker registration/admin creation.
- Worker profile.
- Worker location update.

### Milestone 4: Nearby Discovery

- Nearby Tasks endpoint.
- Radius expansion.
- Distance sorting.

### Milestone 5: Acceptance & Execution

- Worker accepts Task.
- Status transitions.
- Service submissions.

### Milestone 6: Settlement & Operations

- Worker earnings.
- Refund rules.
- Admin monitoring.

## Open Questions قبل كتابة الكود

1. هل Planogram mandatory لكل الخدمات فعلًا، أم يمكن جعله optional لبعض الخدمات حسب التشغيل؟
2. هل Job Order في Secondary Display mandatory أم optional مع Planogram؟
3. هل Freshness Report request من الشركة يحتاج quantity/expiry date كحقول mandatory أم optional، بما أن العامل هو الذي سيرفع القيم الفعلية؟
4. هل السعر الذي تدفعه الشركة يذهب كله للعامل أم يوجد platform fee؟
5. هل يمكن للشركة تعديل Task بعد أن يصبح active؟
6. ما مدة صلاحية الـ Task قبل أن تصبح expired؟
7. هل Worker يستطيع قبول أكثر من Task في نفس الوقت؟
8. هل يتم إظهار العنوان التفصيلي للعامل قبل القبول أم بعد القبول فقط؟
9. هل يجب وجود approval من الشركة بعد إكمال العامل للـ Task؟
10. ما هو default radius والـ max radius للبحث عن Tasks؟
11. هل يوجد cancellation fees بعد قبول العامل؟
12. هل الصور والملفات ستستخدم Media Library الحالية، وهل نحتاج image count/size limits لكل Service؟

## أول Sprint مقترح

للبداية العملية بعد اعتماد الخطة، أفضل Sprint يكون محدودًا كالتالي:

1. تحويل Service Catalog إلى seed/config للخدمات الخمسة ومفاتيحها.
2. تحويل Company Request Forms و Worker Submission Forms إلى validation schemas.
3. تنفيذ Company Task create/list/show.
4. تنفيذ pricing validation.
5. تنفيذ wallet charge عند إنشاء الـ Task.
6. جعل الـ Task يصبح active بعد نجاح الخصم.
7. إضافة tests تغطي happy path وأهم failure cases.

هذا يعطي foundation قوية قبل الدخول في Worker discovery والـ nearby search.
