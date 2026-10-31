<?php

use App\Http\Controllers\Public\LocaleController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::middleware(SetLocale::class)->group(function () {
    Route::view('/', 'public.home')->name('home');
});

Route::post('/language', LocaleController::class)->name('language');
