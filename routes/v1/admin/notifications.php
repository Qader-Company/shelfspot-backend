<?php

use App\Modules\V1\Users\Presentation\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::controller(NotificationController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/unread-count', 'unreadCount');
    Route::patch('/read-all', 'markAllAsRead');
    Route::patch('/{id}/read', 'markAsRead');
});
