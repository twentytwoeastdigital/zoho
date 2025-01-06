<?php

use Illuminate\Support\Facades\Route;
use TwentyTwoEastDigital\Zoho\Http\Controllers\ZohoController;

Route::prefix('zoho')->group(function () {
    Route::get('/authorize', [ZohoController::class, 'authorize'])->name('zoho.authorize');
    Route::get('/callback', [ZohoController::class, 'callback'])->name('zoho.callback');
});