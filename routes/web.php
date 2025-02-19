<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
// Temporary route (routes/web.php)
// Temporary route test (routes/web.php)
Route::get('/test-pusher', function () {
    return [
        'broadcast_driver' => config('broadcasting.default'),
        'pusher_key' => config('broadcasting.connections.pusher.key'),
        'app_env' => env('APP_ENV'),
    ];
});

Route::get(
    '/auth/disconnected', function () {
        return __('auth.disconnected');
    }
)->name('auth.disconnected');
