<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TelegramChatController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    Log::debug('Root route accessed', [
        'authenticated' => auth()->check(),
        'is_admin' => auth()->check() ? auth()->user()->isAdmin() : false
    ]);

    if (auth()->check() && auth()->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('login');
});

// Admin routes - Fix the middleware definition
Route::group(['middleware' => ['web', 'auth', \App\Http\Middleware\AdminMiddleware::class], 'prefix' => 'admin', 'as' => 'admin.'], function () {
    Log::debug('Admin routes group accessed');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('chats', TelegramChatController::class);
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

require __DIR__.'/auth.php';
