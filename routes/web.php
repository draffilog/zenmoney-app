<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TelegramChatController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

// Redirect root to admin dashboard if authenticated
Route::get('/', function () {
    if (auth()->check() && auth()->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('login');
});

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Replace the default dashboard with admin dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('chats', TelegramChatController::class);
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

// Remove or comment out the default dashboard route
// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/auth.php';
