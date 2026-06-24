# Task Lifecycle, Review, Reopen, and Auto-Accept Implementation Plan

## الهدف

تثبيت وتنفيذ دورة حياة واضحة للـ **Task** من وقت إنشائها وحتى قبول تسليمها من الشركة، مع دعم:

- حالات التشغيل المتفق عليها للتاسك.
- تسليم الخدمات باستخدام `task_service_submissions` بدون عمل versions للتسليم.
- قبول أو رفض الشركة للتسليم.
- سبب إجباري عند الرفض.
- إعادة فتح التاسك بواسطة الأدمن بعد الرفض.
- رسائل مراجعة بين الأدمن والشركة مرتبطة بالتاسك.
- قبول تلقائي للتاسك بعد انتهاء فترة مراجعة الشركة.
- حساب نسبة تنفيذ التاسك بناءً على الخدمات التي تم تسليمها.

## الحالة الحالية التي سيتم البناء عليها

يوجد في المشروع أساس جيد لدورة حياة التاسك:

- `TaskStatusEnum` يحتوي أغلب الحالات المطلوبة، لكنه يحتاج تنظيفًا لأن حالة `IN_REVIEW` قيمتها الحالية `completed`.
- `Task` يحتوي بيانات التشغيل الأساسية مثل `date`, `execution_time`, `estimated_duration_minutes`, `expires_at`, `accepted_at`, `started_at`, و `completed_at`.
- `Task` مرتبط بـ `services` و `statusHistories`.
- `TaskServiceSubmission` موجود وسيظل هو مصدر بيانات تسليم كل خدمة داخل التاسك.
- `CompleteTaskUseCase` هو مكان تحويل التاسك بعد انتهاء العامل من التنفيذ.
- يوجد بالفعل scheduler لتشغيل أوامر دورية مثل فشل التاسكات المنتهية.

## حالات التاسك النهائية

الحالات المعتمدة للـ Task ستكون:

| Status | المعنى |
| --- | --- |
| `draft` | التاسك اتعملت لكن لسه متدفعتش بسبب عدم وجود رصيد كافي في محفظة الشركة. |
| `pending` | التاسك اتدفعت واتنشرت، ولسه مفيش عامل اختارها. |
| `started` | العامل اختار التاسك، ولسه موصلش المكان. |
| `in_progress` | العامل وصل المكان وبدأ التنفيذ. |
| `worker_cancelled` | العامل لغى التاسك ومش عايز يكمل. |
| `completed` | العامل خلص التاسك وسلّم كل الخدمات المطلوبة. |
| `rejected` | الشركة رفضت تسليم التاسك. |
| `accepted` | الشركة قبلت تسليم التاسك يدويًا أو تلقائيًا. |
| `reopened` | الأدمن أعاد فتح التاسك بعد رفض الشركة. |
| `failed` | مفيش عامل وافق على التاسك أو وقتها انتهى حسب قواعد الفشل. |

## مبادئ التصميم المتفق عليها

1. **الحالة تكون على التاسك فقط**
   - لا نضيف lifecycle مستقل للتسليم.
   - حالات القبول والرفض وإعادة الفتح ستكون على `tasks.status`.

2. **لا توجد versions للتسليم**
   - يوجد تسليم واحد فعلي للتاسك.
   - عند إعادة التسليم بعد `reopened`، يتم تحديث بيانات التسليم الحالية للخدمات بدل إنشاء إصدارات متعددة.

3. **استمرار استخدام `task_service_submissions`**
   - الجدول سيظل مستخدمًا لتخزين تسليم كل خدمة.
   - سيتم استخدامه لحساب نسبة تنفيذ التاسك: كام خدمة اتنفذت وكام خدمة متبقية.

4. **الرفض يحتاج سبب إجباري**
   - الشركة لا تستطيع رفض التسليم بدون سبب واضح.

5. **الأدمن فقط يستطيع إعادة فتح التاسك**
   - الشركة ترفض التسليم.
   - الأدمن يراجع الرفض.
   - إذا كان الرفض صحيحًا، الأدمن يعمل `reopened`.

6. **الشركة تستطيع التراجع عن الرفض**
   - مسموح بالانتقال من `rejected` إلى `accepted` طالما الأدمن لم يعمل `reopened`.

7. **بعد انتهاء فترة المراجعة، القبول يتم تلقائيًا**
   - إذا مر وقت المراجعة بعد deadline التاسك، تتحول التاسك إلى `accepted` تلقائيًا.
   - بعد انتهاء فترة المراجعة، لا يمكن للشركة عمل rejection.

8. **لا Notifications في هذه المرحلة**
   - الرسائل ستكون مخزنة ويمكن عرضها عند فتح الصفحة أو عمل reload.
   - لا يوجد live chat ولا push notifications ضمن هذه الخطة.

## Flow الأساسي

### 1. إنشاء التاسك والدفع

```text
draft -> pending
```

- `draft`: التاسك اتعملت لكن الشركة لم تدفع بسبب عدم كفاية الرصيد.
- `pending`: تم الدفع أو الحجز المالي بنجاح والتاسك جاهزة للظهور للعمال حسب قواعد الظهور.

### 2. العامل يختار التاسك

```text
pending -> started
```

- العامل اختار التاسك.
- يتم تعيين `assigned_worker_id`.
- يتم تسجيل وقت قبول العامل للتاسك في الحقل الحالي الخاص بذلك.

### 3. العامل يصل الموقع ويبدأ التنفيذ

```text
started -> in_progress
```

- العامل وصل للمكان.
- يتم التحقق من الموقع/geofence.
- يتم تسجيل وقت بداية التنفيذ.

### 4. العامل يسلّم خدمات التاسك

- كل خدمة داخل التاسك يتم تسليمها من خلال `task_service_submissions`.
- كل تسليم خدمة يحتوي على `form_data` والملفات/الوسائط الخاصة بالخدمة.
- يتم تحديث نسبة تنفيذ التاسك بناءً على عدد الخدمات التي تم تسليمها.

### 5. العامل يكمل التاسك

```text
in_progress -> completed
```

- لا يتم السماح بـ `completed` إلا بعد تسليم كل الخدمات المطلوبة.
- عند الانتقال إلى `completed` يتم حساب `auto_accept_at`.

### 6. الشركة تراجع التسليم

الشركة لديها اختيارين خلال فترة المراجعة:

```text
completed -> accepted
```

أو:

```text
completed -> rejected
```

- `accepted`: الشركة قبلت التسليم.
- `rejected`: الشركة رفضت التسليم ويجب كتابة سبب الرفض.

### 7. الشركة تتراجع عن الرفض

```text
rejected -> accepted
```

- مسموح فقط قبل أن يعمل الأدمن `reopened`.
- لا يتم إنشاء تسليم جديد في هذه الحالة.

### 8. الأدمن يعيد فتح التاسك

```text
rejected -> reopened
```

- الأدمن راجع سبب الرفض وقرر أن الشركة عندها حق.
- يتم إعادة فتح التاسك لنفس العامل الحالي مبدئيًا.
- يمكن استخدام reassign الموجود لاحقًا لو احتاج الأدمن تغيير العامل.

### 9. العامل ينفذ مرة أخرى بعد إعادة الفتح

```text
reopened -> in_progress -> completed
```

- بعد `reopened`، العامل يستطيع بدء التنفيذ مرة أخرى.
- عند التسليم مرة أخرى، يتم تحديث `task_service_submissions` الحالية بدل إنشاء versions.
- عند إكمال التاسك مرة أخرى، يتم حساب `auto_accept_at` جديد.

### 10. القبول التلقائي

```text
completed -> accepted
```

- إذا لم تقبل أو ترفض الشركة خلال فترة المراجعة، يتم قبول التاسك تلقائيًا.
- بعد انتهاء فترة المراجعة، لا يمكن عمل rejection.

## تعريف فترة المراجعة والـ Auto Accept

### الحقل المقترح

نضيف على جدول `tasks`:

```text
auto_accept_at
```

هذا الحقل يمثل آخر وقت تستطيع فيه الشركة مراجعة التسليم ورفضه.

### طريقة الحساب

يتم حساب `auto_accept_at` عند انتقال التاسك إلى `completed`.

القاعدة المقترحة:

```text
auto_accept_at = task_deadline + 2 days
```

حيث `task_deadline` يتم حسابه من:

```text
task.date + task.execution_time + task.estimated_duration_minutes
```

مثال:

```text
task deadline: 2026-06-24 15:00
auto_accept_at: 2026-06-26 15:00
```

> إذا كان المطلوب business-wise هو نهاية اليوم الثاني بدل 48 ساعة، يتم تغيير الحساب إلى `deadline date + 2 days at 23:59:59`، لكن التنفيذ الافتراضي المقترح هو `deadline + 2 days`.

### قواعد الرفض بعد انتهاء الوقت

في `CompanyRejectTaskUseCase`:

- إذا `now() >= auto_accept_at`:
  - لا يتم قبول الرفض.
  - يتم منع الانتقال إلى `rejected`.
  - يمكن تنفيذ auto-accept فوري أو إرجاع validation error واضح.

الرسالة المقترحة:

```text
Review window has expired; task can no longer be rejected.
```

## الحقول الجديدة المقترحة على جدول `tasks`

```text
rejected_at
rejection_reason
company_accepted_at
auto_accept_at
auto_accepted_at
reopened_at
reopen_reason
```

### معنى الحقول

| Field | المعنى |
| --- | --- |
| `rejected_at` | وقت رفض الشركة للتسليم. |
| `rejection_reason` | سبب رفض الشركة، إجباري عند الرفض. |
| `company_accepted_at` | وقت قبول الشركة للتسليم، سواء يدويًا أو تلقائيًا. |
| `auto_accept_at` | وقت انتهاء فترة المراجعة وبعده لا يمكن الرفض. |
| `auto_accepted_at` | وقت قبول السيستم للتاسك تلقائيًا. |
| `reopened_at` | وقت إعادة فتح التاسك بواسطة الأدمن. |
| `reopen_reason` | سبب إعادة الفتح من الأدمن، اختياري أو إجباري حسب القرار النهائي. |

## تحديث `TaskStatusEnum`

يجب أن يكون enum النهائي:

```php
enum TaskStatusEnum: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case STARTED = 'started';
    case IN_PROGRESS = 'in_progress';
    case WORKER_CANCELLED = 'worker_cancelled';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';
    case ACCEPTED = 'accepted';
    case REOPENED = 'reopened';
    case FAILED = 'failed';
}
```

### ملاحظات تنفيذية

- استبدال كل استخدامات `TaskStatusEnum::IN_REVIEW` بـ `TaskStatusEnum::COMPLETED`.
- إزالة أو إيقاف استخدام اسم `IN_REVIEW` لأنه مربك، حتى لو كانت قيمته `completed`.

## Use Cases المطلوبة

### 1. `CompanyAcceptTaskUseCase`

#### الهدف

الشركة تقبل التسليم.

#### Transitions المسموحة

```text
completed -> accepted
rejected -> accepted
```

#### قواعد التنفيذ

- الشركة يجب أن تكون مالكة للتاسك.
- لا يمكن قبول task في حالات مثل `pending`, `started`, `in_progress`, `reopened`, `failed`, أو `worker_cancelled`.
- عند القبول:
  - `status = accepted`
  - `company_accepted_at = now()`
  - تسجيل status history.

### 2. `CompanyRejectTaskUseCase`

#### الهدف

الشركة ترفض تسليم التاسك مع سبب.

#### Transition المسموح

```text
completed -> rejected
```

#### قواعد التنفيذ

- الشركة يجب أن تكون مالكة للتاسك.
- التاسك يجب أن تكون `completed`.
- `rejection_reason` مطلوب.
- `now()` يجب أن يكون قبل `auto_accept_at`.
- عند الرفض:
  - `status = rejected`
  - `rejected_at = now()`
  - `rejection_reason = reason`
  - تسجيل status history.

### 3. `AdminReopenTaskUseCase`

#### الهدف

الأدمن يعيد فتح التاسك بعد رفض الشركة.

#### Transition المسموح

```text
rejected -> reopened
```

#### قواعد التنفيذ

- التاسك يجب أن تكون `rejected`.
- عند إعادة الفتح:
  - `status = reopened`
  - `reopened_at = now()`
  - `reopen_reason = reason`
  - `auto_accept_at = null`
  - `auto_accepted_at = null`
  - تسجيل status history.
- يتم إبقاء `assigned_worker_id` كما هو مبدئيًا.
- يتم reset حالة خدمات التاسك لتسمح بإعادة التسليم.

### 4. `AutoAcceptExpiredReviewTasksUseCase`

#### الهدف

قبول التاسكات تلقائيًا بعد انتهاء فترة مراجعة الشركة.

#### Query

```text
status = completed
auto_accept_at <= now()
```

#### Action

```text
completed -> accepted
```

#### البيانات المسجلة

```text
status = accepted
company_accepted_at = now()
auto_accepted_at = now()
```

#### ملاحظات

- يجب استخدام locking/transactions لتفادي race conditions مع accept/reject اليدوي.
- يجب تسجيل status history.

## Commands والجدولة

### Command جديد

```bash
php artisan tasks:auto-accept-expired-review
```

### Scheduler

يتم تشغيله دوريًا:

```php
Schedule::command('tasks:auto-accept-expired-review')->everyMinute();
```

أو كل خمس دقائق إذا كان الحمل مهمًا:

```php
Schedule::command('tasks:auto-accept-expired-review')->everyFiveMinutes();
```

## تعديل Worker Flow بعد `reopened`

### `StartExecuteTaskUseCase` / `CanExecuteTaskRule`

السماح للعامل ببدء التنفيذ من الحالتين:

```text
started
reopened
```

بدل السماح من `started` فقط.

### Transition

```text
reopened -> in_progress
```

### إعادة التسليم

عند تسليم خدمة بعد `reopened`:

- إذا لا يوجد `TaskServiceSubmission` للخدمة، يتم إنشاؤه.
- إذا يوجد submission سابق، يتم تحديثه بنفس الـ record.
- لا يتم إنشاء version جديد.

## Progress Calculation

### الهدف

عرض نسبة تنفيذ التاسك بناءً على الخدمات المسلمة.

### الحقول المقترحة في `TaskResource`

```json
{
  "progress": {
    "total_services": 5,
    "completed_services": 3,
    "remaining_services": 2,
    "percentage": 60
  }
}
```

### طريقة الحساب

```text
total_services = count(task.services)
completed_services = count(services that have submission or completed status)
remaining_services = total_services - completed_services
percentage = completed_services / total_services * 100
```

### ملاحظات

- يجب تحميل relation الخاصة بـ `services.submission` عند الحاجة.
- إذا لم توجد services، تكون النسبة `0` لتجنب division by zero.

## Review Messages

### الهدف

إضافة رسائل غير live بين الأدمن والشركة مرتبطة بالتاسك، تستخدم أثناء مراجعة الرفض.

### Table مقترح

```text
task_review_messages
```

### Columns

```text
id
task_id
sender_id
sender_type أو sender_role
message
created_at
updated_at
```

### قواعد الرسائل

- الرسائل مرتبطة بالتاسك فقط.
- لا يوجد live chat.
- لا يوجد notifications في هذه المرحلة.
- الأدمن يستطيع إرسال رسالة للشركة.
- الشركة تستطيع الرد على الأدمن.
- العامل لا يدخل في هذا النقاش في المرحلة الأولى.

### الحالات المسموح فيها بالرسائل

مبدئيًا:

```text
rejected
```

ويمكن السماح بالقراءة فقط في:

```text
reopened
accepted
```

## API Endpoints المقترحة

### Company

```http
POST /api/v1/company/tasks/{task}/accept
POST /api/v1/company/tasks/{task}/reject
GET  /api/v1/company/tasks/{task}/review-messages
POST /api/v1/company/tasks/{task}/review-messages
```

### Admin

```http
POST /api/v1/admin/tasks/{task}/reopen
GET  /api/v1/admin/tasks/{task}/review-messages
POST /api/v1/admin/tasks/{task}/review-messages
```

### Worker

لا نحتاج endpoint جديد للعامل مبدئيًا، لكن نعدل endpoints الحالية لتسمح بـ:

```text
reopened -> in_progress
```

## تحديث `TaskResource`

يجب إضافة الحقول التالية:

```text
rejected_at
rejection_reason
company_accepted_at
auto_accept_at
auto_accepted_at
reopened_at
reopen_reason
progress
```

### مراجعة Company-facing Status

أي mapping يخفي `accepted`, `rejected`, `completed`, أو `reopened` عن الشركة يجب مراجعته، لأن الشركة الآن تحتاج رؤية هذه الحالات بوضوح.

## Status History

كل transition مهم يجب أن يسجل في `task_status_histories`:

- `in_progress -> completed`
- `completed -> accepted`
- `completed -> rejected`
- `rejected -> accepted`
- `rejected -> reopened`
- `reopened -> in_progress`
- `completed -> accepted` بواسطة auto-accept

### Metadata مقترحة

عند تسجيل history يمكن إضافة metadata حسب الحدث:

```json
{
  "reason": "...",
  "auto_accepted": true,
  "actor_type": "company|admin|system|worker"
}
```

## خطة التنفيذ المقسمة على مراحل

### Phase 1: تنظيف حالات التاسك

- تحديث `TaskStatusEnum`.
- استبدال `IN_REVIEW` بـ `COMPLETED`.
- إضافة `REOPENED`.
- تحديث أي tests متأثرة.

### Phase 2: Migration لحقول المراجعة

- إضافة:
  - `rejected_at`
  - `rejection_reason`
  - `company_accepted_at`
  - `auto_accept_at`
  - `auto_accepted_at`
  - `reopened_at`
  - `reopen_reason`
- تحديث casts و fillable في `Task`.

### Phase 3: تعديل إكمال العامل للتاسك

- تحديث `CompleteTaskUseCase` ليستخدم `COMPLETED`.
- حساب `auto_accept_at` عند الإكمال.
- تسجيل history.

### Phase 4: قبول ورفض الشركة

- إنشاء `CompanyAcceptTaskUseCase`.
- إنشاء `CompanyRejectTaskUseCase`.
- إنشاء request validation للرفض.
- إضافة controller methods.
- إضافة routes.
- إضافة messages في ملفات اللغة.

### Phase 5: إعادة فتح التاسك بواسطة الأدمن

- إنشاء `AdminReopenTaskUseCase`.
- إنشاء request validation اختياري للسبب.
- reset حقول auto-accept عند reopen.
- reset حالة الخدمات للسماح بالتسليم من جديد.
- إضافة controller method و route.

### Phase 6: دعم العامل بعد `reopened`

- تعديل قواعد execute لتسمح بـ `reopened`.
- تعديل تسليم الخدمات ليحدث submission الحالي بدل versions.
- التأكد من أن complete يعمل مرة أخرى بعد إعادة الفتح.

### Phase 7: Auto Accept

- إنشاء `AutoAcceptExpiredReviewTasksUseCase`.
- إنشاء command:
  - `tasks:auto-accept-expired-review`
- جدولة command.
- منع الرفض بعد `auto_accept_at` حتى لو command لم يعمل بعد.

### Phase 8: Progress في Resource

- حساب progress من الخدمات وتسليماتها.
- إضافة progress إلى `TaskResource`.
- التأكد من eager loading لتجنب N+1.

### Phase 9: Review Messages

- إنشاء migration/model/resource للرسائل.
- إضافة endpoints للأدمن والشركة.
- منع العامل من الوصول للرسائل.
- السماح بالرسائل في حالة `rejected` مبدئيًا.

### Phase 10: Tests

إضافة وتحديث اختبارات تغطي:

1. العامل يكمل التاسك:
   ```text
   in_progress -> completed
   ```
2. عند الإكمال يتم حساب `auto_accept_at`.
3. الشركة تقبل التسليم:
   ```text
   completed -> accepted
   ```
4. الشركة ترفض التسليم بسبب إجباري:
   ```text
   completed -> rejected
   ```
5. الرفض بدون سبب يفشل.
6. الرفض بعد `auto_accept_at` يفشل.
7. الـ command يحول التاسك تلقائيًا:
   ```text
   completed -> accepted
   ```
8. الشركة تتراجع عن الرفض:
   ```text
   rejected -> accepted
   ```
9. الأدمن يعيد فتح التاسك:
   ```text
   rejected -> reopened
   ```
10. العامل يبدأ مرة أخرى:
    ```text
    reopened -> in_progress
    ```
11. التسليم بعد reopen يحدث نفس `task_service_submissions` بدل إنشاء versions.
12. progress يرجع total/completed/remaining/percentage بشكل صحيح.
13. رسائل الأدمن والشركة تعمل على التاسك المرفوضة.
14. العامل لا يستطيع الوصول إلى review messages.

## قرارات مؤجلة أو تحتاج تأكيد

1. هل `auto_accept_at` يكون:
   - `deadline + 2 days`، أم
   - نهاية اليوم الثاني بعد deadline؟

   القرار المقترح حاليًا: `deadline + 2 days`.

2. هل `reopen_reason` مطلوب إجباريًا للأدمن؟

   القرار المقترح حاليًا: اختياري، ويمكن جعله إجباريًا إذا احتجنا audit أقوى.

3. هل عند `reopened` يتم reset كل الخدمات أم فقط الخدمات المرفوضة؟

   بما أنه لا يوجد versions ولا rejection على مستوى service، القرار المقترح: reset كل الخدمات.

4. هل نحافظ على بيانات التسليم القديمة عند إعادة التسليم؟

   القرار المقترح: نعم، لكن يتم overwrite لنفس `TaskServiceSubmission` عند إرسال بيانات جديدة.

## ترتيب PRs المقترح

### PR 1: Core Lifecycle + Review Window

- Status enum cleanup.
- Migration لحقول القبول/الرفض/reopen/auto-accept.
- Complete task إلى `completed`.
- Company accept/reject.
- Admin reopen.
- Auto accept command/use case.
- Tests للـ lifecycle الأساسي.

### PR 2: Progress from Service Submissions

- Progress calculation.
- TaskResource updates.
- Tests للـ progress.

### PR 3: Review Messages

- Messages table/model/resource.
- Company/Admin message endpoints.
- Tests للرسائل والصلاحيات.

## النتيجة المتوقعة بعد التنفيذ

بعد تنفيذ الخطة، دورة الحياة الأساسية ستكون:

```text
draft
-> pending
-> started
-> in_progress
-> completed
-> accepted
```

وسيناريو الرفض وإعادة الفتح سيكون:

```text
completed
-> rejected
-> reopened
-> in_progress
-> completed
-> accepted
```

وسيناريو القبول التلقائي سيكون:

```text
completed
-> accepted
```

بشرط أن يصل الوقت إلى:

```text
auto_accept_at <= now()
```
