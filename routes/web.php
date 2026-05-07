<?php

use App\Http\Controllers\ApplicationController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::livewire('device/login', 'auth.device-login')->name('device.login');

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('users', 'pages.users.index')->name('users.index');
    Route::view('permissions', 'pages.permissions.index')->name('permissions.index');
    Route::view('plans', 'pages.plans.index')->name('plans.index');
    Route::view('user-profiles', 'pages.user-profiles.index')->name('user-profiles.index');
    Route::livewire('record-type-defaults', 'admin.record-type-defaults')->name('record-type-defaults.index');
    Route::livewire('record-types', 'admin.record-type-manager')->name('record-types.index');
    Route::livewire('record-patterns', 'admin.record-pattern-manager')->name('record-patterns.index');
    Route::resource('applications', ApplicationController::class)->names('applications');
});

require __DIR__.'/settings.php';
