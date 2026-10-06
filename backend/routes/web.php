<?php

use App\Http\Controllers\AdminLanguageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('admin-language/{locale}', AdminLanguageController::class)
    ->middleware('auth')
    ->name('admin.language');
