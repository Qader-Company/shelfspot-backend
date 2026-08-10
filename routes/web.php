<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/notification-lab', 'notification-lab');

if (config('notification_lab.sending_enabled')) {
    require __DIR__.'/notification-lab.php';
}
