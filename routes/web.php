<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\ZenMoneyProfileController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

// Root route
Route::get('/', function () {
    if (auth()->check() && auth()->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('login');
});

// Auth routes
require __DIR__.'/auth.php';

// Admin routes
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('/settings/token', [AdminSettingsController::class, 'index'])->name('settings.token');

    // Чаты
    Route::get('/chats', [ChatController::class, 'index'])->name('chats.index');
    Route::get('/chats/create', [ChatController::class, 'create'])->name('chats.create');
    Route::post('/chats', [ChatController::class, 'store'])->name('chats.store');
    Route::get('/chats/{chat}', [ChatController::class, 'show'])->name('chats.show');
    Route::get('/chats/{chat}/edit', [ChatController::class, 'edit'])->name('chats.edit');
    Route::put('/chats/{chat}', [ChatController::class, 'update'])->name('chats.update');
    Route::delete('/chats/{chat}', [ChatController::class, 'destroy'])->name('chats.destroy');

    // ZenMoney
    Route::get('/zenmoney/profile', [ZenMoneyProfileController::class, 'index'])->name('zenmoney.profile');
});
