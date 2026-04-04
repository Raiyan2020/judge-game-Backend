<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CountryController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\LastUpdateController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'setLocale'], function () {

    Route::group(['prefix' => 'auth'], function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('verify-code', [AuthController::class, 'verifyCode']);
    });

    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::group(['prefix' => 'auth'], function () {
            Route::get('profile', [ProfileController::class, 'show']);
            Route::post('profile', [ProfileController::class, 'update']);
            Route::post('update-setting', [ProfileController::class, 'updateSetting']);
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    Route::get('home',HomeController::class);
    Route::get('countries',CountryController::class);
    Route::get('last-updates', LastUpdateController::class);

});
