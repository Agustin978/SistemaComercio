<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'storefront.welcome')->name('storefront.home');

Route::middleware(['auth', 'role:merchant_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');
});

Route::middleware(['auth', 'role:super_admin'])->prefix('hub')->name('hub.')->group(function () {
    Route::view('/', 'hub.dashboard')->name('dashboard');
});
