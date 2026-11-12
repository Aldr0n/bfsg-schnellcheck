<?php

use App\Http\Controllers\AuditController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'audits/create')->name('home');
Route::post('/', [AuditController::class, 'store'])->middleware('throttle:audits')->name('audits.store');
Route::get('/bericht/{audit}', [AuditController::class, 'show'])->name('audits.show');
