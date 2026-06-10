# Tasks & Workers Roadmap

## الرؤية النهائية

نريد بناء دورة تشغيل كاملة تبدأ من إنشاء الشركة للـ task، ثم حجز/خصم قيمتها مؤقتًا من محفظة الشركة، ثم ظهورها للعمال القريبين في يوم التنفيذ المحدد فقط، ثم قبولها والذهاب لموقع المتجر خلال نافذة زمنية محددة، ثم تنفيذ كل الخدمات وتسليم نماذجها، ثم إغلاقها أو فشلها مع حفظ تاريخ الحالة والقرارات المالية. في هذه المرحلة سنركز على Workers module وتكملة Tasks lifecycle، مع تأجيل Worker Wallet بالكامل حتى ننتهي من التسجيل والتشغيل الأساسي.

## مبادئ التنفيذ

- نفصل Worker domain عن Worker Wallet؛ أي تسجيل العامل وملفه وحركة التاسكات لا يعتمدوا على wallet في هذه المرحلة.
- كل تغيير حالة للـ task يجب أن يكون داخل UseCase واضح، وليس من الـ controller مباشرة.
- أي transition مهم في task lifecycle يجب أن يسجل في `task_status_histories` مع actor ونوعه والسبب إن وجد.
- العامل يرى ويتعامل فقط مع التاسكات المسموح بها حسب تاريخ التاسك، القرب الجغرافي، حالتها، والعامل المعيّن لها.
- الشركة ترى وتتحكم فقط في التاسكات التابعة للـ tenant الحالي، ولا ترى تفاصيل تشغيل العامل الداخلية مثل cancel reason أو reassign history إلا كحالة عامة للـ task.
- الحالة المعروضة للشركة أبسط من الحالة الداخلية: `pending` ثم `in_progress` ثم `completed` أو `failed`.
- حذف الشركة للـ task هو soft/company delete يخفيها من واجهة الشركة فقط، أما hard delete الحقيقي فهو صلاحية Shelf Spot Admin فقط.

## Task lifecycle المعتمد

### Company-facing statuses

- `pending`: task اتعمل واتخصمت/اتحجزت قيمته من wallet الشركة، ولسه لم يبدأ تنفيذها فعليًا.
- `in_progress`: task تم قبولها/بدأ التعامل معها من Worker أو Admin operational flow.
- `completed`: كل الخدمات المطلوبة اتعمل لها submission صحيح وتم إغلاق التاسك بنجاح.
- `failed`: لم يبدأ أحد تنفيذ التاسك في الوقت المسموح، أو انتهت صلاحيتها بدون Worker، أو فشلت بقواعد التشغيل المتفق عليها.

### Internal operational statuses

- `pending`: جاهزة للظهور في يوم التنفيذ ولم يتم قبولها بعد.
- `accepted`: Worker قبل التاسك ويجب أن يصل لموقع المتجر خلال 15 دقيقة.
- `in_progress`: Worker عمل start من موقع المتجر أو قريب منه حسب geofence.
- `completed`: كل services مكتملة.
- `failed`: لم تُقبل/لم تبدأ ضمن النوافذ الزمنية.
- `worker_cancelled`: Worker كنسل وكتب السبب، وتنتظر reassign من Admin أو fallback rule.
- `company_deleted`: محذوفة من واجهة الشركة فقط.
- `admin_deleted`: hard delete أو archival النهائي من Shelf Spot Admin حسب السياسة.

### قواعد مهمة

- الـ task تظهر في available workers فقط في `date` المحدد لها، وبشرط أنها `pending`، مدفوعة/محجوزة ماليًا، غير معيّنة، وداخل radius العامل.
- بعد accept يبدأ عدّاد 15 دقيقة؛ لا يمكن start بعدها إلا حسب سياسة إعادة الفتح/الفشل التي يقررها Admin.
- start يتطلب إرسال موقع العامل الحالي والتحقق أنه داخل geofence موقع التاسك أو ضمن tolerance قريب منه.
- Worker لا يستطيع إكمال task مباشرة؛ يجب أن يفتح كل service داخلها ويعمل submit للـ form والملفات المطلوبة حسب نوع الخدمة.
- لو Worker cancelled، الشركة تظل ترى task كـ `in_progress`، والـ Admin يستطيع reassign لعامل آخر متاح ولا يملك tasks `in_progress`.

## المرحلة 1: Worker module foundation & registration

### الهدف
تجهيز موديول العامل كأساس قابل للاستخدام بدون الدخول في Worker Wallet.

### الخطوات

1. تجهيز `Worker` model:
   - fillable/casts.
   - علاقة `user`.
   - علاقات tasks assigned للعامل.
   - حقول/حالة availability لتسهيل منع reassign لعامل لديه task `in_progress`.
2. تجهيز Worker repository/provider:
   - create/find/update/list basics.
   - bind interface في provider.
3. تجهيز Worker registration:
   - request validation خاص بالعامل.
   - create worker use case ينشئ `users` بنوع `worker` ثم ينشئ `workers`.
   - فتح public route لـ `/auth/worker/register`.
4. تعديل auth registration flow ليستقبل company و worker.
5. تعديل `WorkerResource` ليعرض بيانات user + worker بشكل صحيح.

### Acceptance criteria

- يمكن تسجيل عامل جديد من auth register بنفس token verification flow الحالي.
- العامل يكون له user type = `worker` وسجل في جدول `workers`.
- Resource لا يعتمد على أعمدة غير موجودة في جدول workers.

## المرحلة 2: Worker profile & dated nearby task access

### الهدف
إتاحة endpoints أساسية للعامل قبل تنفيذ lifecycle، مع تثبيت ظهور التاسكات حسب التاريخ والقرب.

### الخطوات

1. تسجيل worker routes في `config/modules.php`.
2. إضافة worker controller/profile endpoint:
   - `GET /api/v1/worker/profile`.
   - `PATCH /api/v1/worker/location` لتحديث آخر موقع.
3. إضافة task list للعامل:
   - available tasks: `pending`، تاريخها هو تاريخ اليوم المطلوب للتنفيذ، مدفوعة/محجوزة ماليًا، غير معيّنة، وداخل radius العامل.
   - my tasks: assigned to current worker مع الحالات الداخلية المسموحة.
4. إضافة authorization helpers للحصول على current worker.
5. إضافة distance calculation و radius controls بشكل أولي.

### Acceptance criteria

- العامل المسجل يستطيع جلب profile الخاص به.
- يستطيع تحديث الموقع.
- يستطيع رؤية available/my tasks بدون تغيير lifecycle.
- لا تظهر task قبل يوم تنفيذها أو بعد فشلها/حذفها من الشركة.

## المرحلة 3: Task lifecycle transitions

### الهدف
تطبيق دورة حياة task من pending حتى completed/failed مع فصل الحالة المعروضة للشركة عن الحالات التشغيلية الداخلية.

### الخطوات

1. إضافة `TaskStatusHistory` model/relation.
2. إضافة service/use case لتسجيل status history.
3. إضافة use cases:
   - `AcceptTaskUseCase` يحول internal status من `pending` إلى `accepted`، يعيّن العامل، يضبط `accepted_at` و `start_deadline_at = accepted_at + 15 minutes`، ويجعل company-facing status = `in_progress`.
   - `StartTaskUseCase` يحول `accepted` إلى `in_progress` بعد التحقق من موقع العامل داخل geofence التاسك.
   - `SubmitTaskServiceUseCase` يحفظ submission لكل service.
   - `CompleteTaskUseCase` يحول `in_progress` إلى `completed` بعد اكتمال كل الخدمات.
   - `FailExpiredTaskUseCase` يحول التاسكات غير المقبولة/غير المبدوءة ضمن الوقت إلى `failed` حسب قواعد الـ scheduler.
   - `WorkerCancelTaskUseCase` يحفظ سبب إلغاء العامل ويحرك internal status إلى `worker_cancelled` بدون كشف السبب للشركة.
   - `AdminReassignTaskUseCase` يعيد تعيين task لعامل متاح لا يملك tasks `in_progress`، وتظل company-facing status = `in_progress`.
4. إضافة routes/controllers للـ worker actions.
5. ضبط guards بحيث العامل لا يغير إلا task assigned له بعد القبول.
6. إضافة scheduler/command لفحص انتهاء تاريخ التاسك ونافذة الـ 15 دقيقة.

### Acceptance criteria

- لا يمكن قبول task غير `pending` أو ليست في يوم التنفيذ أو already assigned.
- لا يمكن بدء task غير `accepted` أو ليست assigned للعامل الحالي أو خارج geofence.
- لا يمكن إكمال task إلا بعد اكتمال خدماتها.
- Worker cancel يسجل reason داخليًا، ولا يغير الحالة الظاهرة للشركة من `in_progress`.
- Admin reassign لا يختار عامل لديه task `in_progress`.
- كل status change يسجل history.

## المرحلة 4: Task service submissions

### الهدف
تمكين العامل من تسليم بيانات كل service داخل task.

### الخطوات

1. إضافة `TaskServiceSubmission` model/resource.
2. بناء request validation بناءً على output form لكل service type إن وجد.
3. إضافة submit/update/complete submission use cases.
4. تحديث `TaskServiceStatusEnum` flow من `pending` إلى `in_progress` إلى `completed`.
5. ربط إكمال كل services بإمكانية إكمال task.
6. دعم الملفات المطلوبة مثل before/after pictures أو picture files حسب نوع الخدمة.

### Acceptance criteria

- العامل يستطيع تسليم form data لكل service assigned له.
- service لا يكتمل إلا بعد validation صحيح.
- task لا يكتمل إلا بعد اكتمال كل task services.

## المرحلة 5: Company task controls, delete rules & reschedule

### الهدف
تجهيز تحكم الشركة ومتابعة lifecycle مع قواعد edit/delete/refund/reschedule.

### الخطوات

1. Company actions by status:
   - `pending`: يمكن delete من واجهة الشركة، ويمكن edit قبل بدء التنفيذ مع إعادة حساب السعر.
   - `in_progress`: يمكن delete من واجهة الشركة فقط، بدون تعطيل التشغيل الداخلي.
   - `completed`: يمكن delete من واجهة الشركة فقط.
   - `failed`: يمكن delete مع refund حسب قيمة الحجز، ويمكن edit/reschedule.
2. Reschedule/edit failed task:
   - إعادة حساب السعر الجديد بعد تعديل الخدمات/المنتجات/التاريخ.
   - مقارنة السعر الجديد بالسعر القديم المحجوز.
   - لو السعر الجديد أعلى: خصم الفرق من wallet الشركة.
   - لو السعر الجديد أقل: رد الفرق للشركة.
   - بعد نجاح الحركة المالية تعود task إلى `pending` بتاريخها الجديد.
3. Soft/company delete:
   - إضافة `company_deleted_at` أو status/visibility flag يخفي task من الشركة فقط.
   - لا يتم حذف records فعليًا من DB بسبب audit والعمليات.
4. Shelf Spot Admin hard delete:
   - endpoint منفصل للـ Admin فقط للحذف النهائي/الأرشفة حسب السياسة.
5. عرض status history في show task، مع فلترة تفاصيل worker الداخلية عن الشركة.
6. تحسين filters للـ tasks:
   - assigned_worker_id للـ Admin فقط أو حسب الصلاحية.
   - date range.
   - company-facing status/payment status.

### Acceptance criteria

- الشركة تستطيع متابعة history وحالة كل task بدون تفاصيل داخلية غير مسموحة عن العامل.
- أفعال الشركة تختلف حسب status كما هو موضح.
- failed reschedule يطبق فرق السعر في wallet بشكل صحيح.
- حذف الشركة لا يحذف البيانات فعليًا ولا يظهر التاسك للشركة بعده.
- hard delete متاح للـ Shelf Spot Admin فقط.

## المرحلة 6: Tests & hardening

### الهدف
تثبيت السلوك قبل الرجوع لـ Worker Wallet.

### الخطوات

1. Feature tests لتسجيل worker.
2. Feature tests لـ company create task + wallet charge/hold.
3. Feature tests لـ dated nearby discovery.
4. Feature tests لـ worker accept/start geofence/submit/complete.
5. Tests للحالات المرفوضة:
   - قبول task already assigned.
   - قبول task قبل/بعد تاريخ تنفيذها.
   - start بعد 15 دقيقة.
   - start خارج geofence.
   - بدء task غير assigned.
   - complete قبل services.
6. Tests لـ worker cancel + admin reassign لعامل متاح.
7. Tests لـ company actions by status.
8. Tests لـ failed reschedule wallet difference.
9. Tests لـ company soft delete و admin hard delete.
10. مراجعة validation والترجمات.

### Acceptance criteria

- Happy path كامل مغطى بالاختبارات.
- أهم failed transitions مغطاة.
- القواعد المالية في failed/reschedule/delete مغطاة.

## مؤجل: Worker Wallet

سنؤجل كل ما يلي حتى اكتمال Worker registration + Task lifecycle:

- Worker wallet balance calculations.
- Worker earning transactions عند complete task.
- Withdrawal requests endpoints.
- Admin approval/payment للwithdrawals.
- إصلاح repository الحالي الذي يشير إلى `WorkersWallet` model غير موجود.
