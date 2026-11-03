<?php

use App\Http\Controllers\Public\GrievanceController;
use App\Http\Controllers\Public\StatusController;
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
        // The grievance form is the home page: the company site's button and the QR poster land on it (P1, P10).
        Route::get('/', [GrievanceController::class, 'create'])->name('home');
        Route::post('/', [GrievanceController::class, 'store'])->name('grievance.store');
        Route::get('/received', [GrievanceController::class, 'received'])->name('grievance.received');
        Route::post('/received', [GrievanceController::class, 'done'])->name('grievance.done');

        // The status page (P8): reference and PIN by POST only, never in an address.
        Route::get('/status', [StatusController::class, 'lookup'])->name('status.lookup');
        Route::post('/status', [StatusController::class, 'check'])->name('status.check');
        Route::get('/status/case', [StatusController::class, 'show'])->name('status.show');
        Route::post('/status/case', [StatusController::class, 'reply'])->name('status.reply');
        Route::post('/status/close', [StatusController::class, 'close'])->name('status.close');
    });
