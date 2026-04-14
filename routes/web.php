<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::livewire('device/login', 'auth.device-login')->name('device.login');

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('users', 'users.index')->name('users.index');
    Route::view('permissions', 'permissions.index')->name('permissions.index');
});

require __DIR__.'/settings.php';
