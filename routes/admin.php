<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\SettingController;
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
    ]);
    Route::PUT('countries/change-status/{country}', [CountryController::class, 'changeStatus'])->name('countries.changeStatus');
    Route::PUT('banners/change-status/{banner}', [BannerController::class, 'changeStatus'])->name('banners.changeStatus');
});