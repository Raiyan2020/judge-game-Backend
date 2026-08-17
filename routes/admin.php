<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\LastUpdateController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\PackageSubscriptionController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RoleActionController;
use App\Http\Controllers\Admin\RoleTitleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TipController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['guest:admin', 'localization']], function () {
    Route::get('login', [LoginController::class, 'create'])
        ->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::group(['middleware' => ['auth:admin', 'adminActive', 'localization']], function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
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
        'role-titles'=>RoleTitleController::class
    ]);
    Route::get('subscriptions', [PackageSubscriptionController::class,'index'])->name('subscriptions.index');
    Route::get('groups', [GroupController::class, 'index'])->name('groups.index');
    Route::get('groups/{group}', [GroupController::class, 'show'])->name('groups.show');
    Route::get('contacts', [ContactController::class,'index'])->name('contacts.index');
    Route::get('contacts/{contact}', [ContactController::class,'show'])->name('contacts.show');
    Route::delete('contacts/{contact}', [ContactController::class,'destroy'])->name('contacts.destroy');
    Route::PUT('countries/change-status/{country}', [CountryController::class, 'changeStatus'])->name('countries.changeStatus');
    Route::PUT('banners/change-status/{banner}', [BannerController::class, 'changeStatus'])->name('banners.changeStatus');
    Route::PUT('tips/change-status/{tip}', [TipController::class, 'changeStatus'])->name('tips.changeStatus');
    Route::PUT('last-updates/change-status/{lastUpdate}', [LastUpdateController::class, 'changeStatus'])->name('last-updates.changeStatus');
    Route::PUT('coupons/change-status/{coupon}', [CouponController::class, 'changeStatus'])->name('coupons.changeStatus');
    Route::PUT('packages/change-status/{package}', [PackageController::class, 'changeStatus'])->name('packages.changeStatus');
    Route::get('role-actions', [RoleActionController::class, 'index'])->name('role-actions.index');
    Route::get('role-actions/{role}/edit', [RoleActionController::class, 'edit'])->name('role-actions.edit');
    Route::get('role-actions/{role}', [RoleActionController::class, 'show'])->name('role-actions.show');
    Route::post('role-actions', [RoleActionController::class, 'store'])->name('role-actions.store');
    Route::get('roles-actions/{role}', [RoleActionController::class, 'getActions'])
        ->name('roles-actions.get');

});