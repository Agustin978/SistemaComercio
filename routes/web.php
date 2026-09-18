<?php

use App\Catalog\Livewire\CategoryForm;
use App\Catalog\Livewire\CategoryIndex;
use App\Catalog\Livewire\MerchantDashboard;
use App\Catalog\Livewire\ProductForm;
use App\Catalog\Livewire\ProductIndex;
use App\Inventory\Livewire\StockAdjustment;
use Illuminate\Support\Facades\Route;

Route::view('/', 'storefront.welcome')->name('storefront.home');

Route::middleware(['auth', 'role:merchant_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', MerchantDashboard::class)->name('dashboard');

    Route::get('/categories', CategoryIndex::class)->name('categories.index');
    Route::get('/categories/create', CategoryForm::class)->name('categories.create');
    Route::get('/categories/{category}/edit', CategoryForm::class)->name('categories.edit');

    Route::get('/products', ProductIndex::class)->name('products.index');
    Route::get('/products/create', ProductForm::class)->name('products.create');
    Route::get('/products/{product}/edit', ProductForm::class)->name('products.edit');
    Route::get('/products/{product}/stock', StockAdjustment::class)->name('stock.adjust');
});

Route::middleware(['auth', 'role:super_admin'])->prefix('hub')->name('hub.')->group(function () {
    Route::view('/', 'hub.dashboard')->name('dashboard');
});
