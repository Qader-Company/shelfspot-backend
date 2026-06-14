<?php

return [
    'validation' => [
        'accept_pending_only' => 'لا يمكن قبول المهمة إلا وهي في حالة انتظار.',
        'accept_charged_only' => 'يجب خصم/حجز قيمة المهمة قبل قبولها.',
        'accept_deleted_task' => 'لا يمكن قبول مهمة محذوفة.',
        'accept_unassigned_only' => 'هذه المهمة مخصصة بالفعل لعامل آخر.',
        'accept_execution_date_only' => 'لا يمكن قبول المهمة إلا في يوم تنفيذها.',
        'start_accepted_only' => 'لا يمكن بدء المهمة إلا بعد قبولها.',
        'worker_not_assigned' => 'هذه المهمة غير مخصصة للعامل الحالي.',
        'start_deadline_expired' => 'انتهت نافذة بدء هذه المهمة.',
        'start_outside_geofence' => 'يجب أن تكون داخل أو بالقرب من موقع المهمة لبدئها.',
        'minimum_price' => 'يجب ألا يقل سعر الخدمة عن :price.',
        'minimum_execution_time' => 'يجب ألا يقل وقت تنفيذ الخدمة عن :minutes دقيقة.',
        'required_file' => 'هذه الخدمة تتطلب رفع الملف المطلوب.',
        'product_not_in_company' => 'المنتج المحدد لا يتبع الشركة الحالية.',
        'service_not_in_task' => 'هذه الخدمة لا تتبع المهمة المحددة.',
        'submit_in_progress_only' => 'لا يمكن تسليم خدمات المهمة إلا بعد بدء المهمة.',
        'submitted_product_not_in_task_service' => 'المنتج المرسل ليس ضمن خدمة هذه المهمة.',
        'complete_in_progress_only' => 'لا يمكن إكمال المهمة إلا بعد أن تكون قيد التنفيذ.',
        'complete_requires_services' => 'يجب أن تحتوي المهمة على خدمات قبل إكمالها.',
        'complete_requires_completed_services' => 'يجب إكمال كل خدمات المهمة قبل إكمال المهمة.',
        'cancel_active_only' => 'لا يمكن للعامل إلغاء المهمة إلا بعد قبولها أو أثناء تنفيذها.',
        'reassign_cancelled_only' => 'لا يمكن إعادة تعيين المهمة إلا إذا كانت ملغاة من العامل أو مقبولة.',
        'reassign_active_worker_only' => 'لا يمكن إعادة تعيين المهمة إلا لعامل نشط.',
        'reassign_worker_busy' => 'هذا العامل لديه مهمة قيد التنفيذ بالفعل.',
    ],
];
