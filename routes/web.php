<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    $locale = in_array($request->cookie('locale'), SetLocale::LOCALES, true) ? $request->cookie('locale') : config('app.locale');

    return redirect()->route('home', ['locale' => $locale]);
});

Route::prefix('{locale}')
    ->where(['locale' => implode('|', SetLocale::LOCALES)])
    ->middleware(SetLocale::class)
    ->group(function () {
        Route::view('/', 'public.home')->name('home');
    });
