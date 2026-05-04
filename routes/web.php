<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
  
    return view('welcome');
});


Route::get('success', [\App\Http\Controllers\Payment\WebhookController::class, 'success'])->name('payment.success');
Route::get('error', [\App\Http\Controllers\Payment\WebhookController::class, 'error'])->name('payment.error');
//Route::get('/change-language/{lang}',[\App\Http\Controllers\LangController::class,'changeLang'])->name('change-language');
