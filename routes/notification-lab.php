<?php

use App\Support\NotificationLab\SendTestNotificationController;
use Illuminate\Support\Facades\Route;

Route::post('/notification-lab/send', SendTestNotificationController::class)
    ->middleware('throttle:10,1')
    ->name('notification-lab.send');
