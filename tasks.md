# Tasks & Workers Roadmap

## الرؤية النهائية

نريد بناء دورة تشغيل كاملة تبدأ من إنشاء الشركة للـ task، ثم ظهورها للعامل المناسب، ثم قبولها وتنفيذها وتسليم كل خدماتها، ثم إغلاقها مع حفظ تاريخ الحالة. في هذه المرحلة سنركز على Workers module وتكملة Tasks lifecycle، مع تأجيل Worker Wallet بالكامل حتى ننتهي من التسجيل والتشغيل الأساسي.

## مبادئ التنفيذ

- نفصل Worker domain عن Worker Wallet؛ أي تسجيل العامل وملفه وحركة التاسكات لا يعتمدوا على wallet في هذه المرحلة.
- كل تغيير حالة للـ task يجب أن يكون داخل UseCase واضح، وليس من الـ controller مباشرة.
- أي transition مهم في task lifecycle يجب أن يسجل في `task_status_histories`.
- العامل يرى ويتعامل فقط مع التاسكات المسموح بها حسب حالتها والعامل المعيّن لها.
- الشركة ترى وتتحكم فقط في التاسكات التابعة للـ tenant الحالي.

## المرحلة 1: Worker module foundation & registration

### الهدف
تجهيز موديول العامل كأساس قابل للاستخدام بدون الدخول في Worker Wallet.

### الخطوات

1. تجهيز `Worker` model:
   - fillable/casts.
   - علاقة `user`.
   - علاقات tasks assigned للعامل.
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

## المرحلة 2: Worker profile & task access shell

### الهدف
إتاحة endpoints أساسية للعامل قبل تنفيذ lifecycle.

### الخطوات

1. تسجيل worker routes في `config/modules.php`.
2. إضافة worker controller/profile endpoint:
   - `GET /api/v1/worker/profile`.
   - `PATCH /api/v1/worker/location` لتحديث آخر موقع.
3. إضافة task list للعامل:
   - available tasks: `active` وغير معيّنة أو حسب قواعد القرب لاحقًا.
   - my tasks: assigned to current worker.
4. إضافة authorization helpers للحصول على current worker.

### Acceptance criteria

- العامل المسجل يستطيع جلب profile الخاص به.
- يستطيع تحديث الموقع.
- يستطيع رؤية available/my tasks بدون تغيير lifecycle.

## المرحلة 3: Task lifecycle transitions

### الهدف
تطبيق دورة حياة task من active حتى completed/declined/cancelled.

### الخطوات

1. إضافة `TaskStatusHistory` model/relation.
2. إضافة service/use case لتسجيل status history.
3. إضافة use cases:
   - `AcceptTaskUseCase` يحول `active` إلى `accepted` ويعيّن العامل.
   - `DeclineTaskUseCase` يحفظ السبب ويحول task إلى `declined` عند اللزوم.
   - `StartTaskUseCase` يحول `accepted` إلى `in_progress`.
   - `CompleteTaskUseCase` يحول `in_progress` إلى `completed` بعد اكتمال كل الخدمات.
   - Company/Admin cancellation flow مع refund باستخدام `RefundTaskWalletUseCase` عند الحاجة.
4. إضافة routes/controllers للـ worker actions.
5. ضبط guards بحيث العامل لا يغير إلا task assigned له بعد القبول.

### Acceptance criteria

- لا يمكن قبول task غير active.
- لا يمكن بدء task غير accepted أو ليست assigned للعامل الحالي.
- لا يمكن إكمال task إلا بعد اكتمال خدماتها.
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

### Acceptance criteria

- العامل يستطيع تسليم form data لكل service assigned له.
- service لا يكتمل إلا بعد validation صحيح.
- task لا يكتمل إلا بعد اكتمال كل task services.

## المرحلة 5: Company task controls & observability

### الهدف
تجهيز تحكم الشركة ومتابعة lifecycle.

### الخطوات

1. endpoints للشركة لإلغاء task قبل قبولها/أثناء حالات مسموحة.
2. عرض status history في show task.
3. تحسين filters للـ tasks:
   - assigned_worker_id.
   - date range.
   - status/payment status.
4. إضافة expiry handling لاحقًا إن احتجنا scheduler.

### Acceptance criteria

- الشركة تستطيع متابعة history وحالة كل task.
- الشركة تستطيع إلغاء task ضمن القواعد المتفق عليها.

## المرحلة 6: Tests & hardening

### الهدف
تثبيت السلوك قبل الرجوع لـ Worker Wallet.

### الخطوات

1. Feature tests لتسجيل worker.
2. Feature tests لـ company create task + wallet charge.
3. Feature tests لـ worker accept/start/submit/complete.
4. Tests للحالات المرفوضة:
   - قبول task already assigned.
   - بدء task غير assigned.
   - complete قبل services.
5. مراجعة validation والترجمات.

### Acceptance criteria

- Happy path كامل مغطى بالاختبارات.
- أهم failed transitions مغطاة.

## مؤجل: Worker Wallet

سنؤجل كل ما يلي حتى اكتمال Worker registration + Task lifecycle:

- Worker wallet balance calculations.
- Worker earning transactions عند complete task.
- Withdrawal requests endpoints.
- Admin approval/payment للwithdrawals.
- إصلاح repository الحالي الذي يشير إلى `WorkersWallet` model غير موجود.
