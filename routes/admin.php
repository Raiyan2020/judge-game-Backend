<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\LastUpdateController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TipController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['guest:admin', 'localization']], function () {
    Route::get('login', [LoginController::class, 'create'])
        ->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::group(['middleware' => ['auth:admin', 'localization']], function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::resources([
        'admins' => AdminController::class,
        'countries' => CountryController::class,
        'users' => UserController::class,
        'banners' => BannerController::class,
        'settings' => SettingController::class,
        'tips' => TipController::class,
        'last-updates' => LastUpdateController::class,
        'coupons' => CouponController::class,
        'packages' =>PackageController::class,
    ]);
    Route::get('contacts', [ContactController::class,'index'])->name('contacts.index');
    Route::delete('contacts/{contact}', [ContactController::class,'destroy'])->name('contacts.destroy');
    Route::PUT('countries/change-status/{country}', [CountryController::class, 'changeStatus'])->name('countries.changeStatus');
    Route::PUT('banners/change-status/{banner}', [BannerController::class, 'changeStatus'])->name('banners.changeStatus');
    Route::PUT('tips/change-status/{tip}', [TipController::class, 'changeStatus'])->name('tips.changeStatus');
    Route::PUT('last-updates/change-status/{lastUpdate}', [LastUpdateController::class, 'changeStatus'])->name('last-updates.changeStatus');
    Route::PUT('coupons/change-status/{coupon}', [CouponController::class, 'changeStatus'])->name('coupons.changeStatus');
    Route::PUT('packages/change-status/{package}', [PackageController::class, 'changeStatus'])->name('packages.changeStatus');

});