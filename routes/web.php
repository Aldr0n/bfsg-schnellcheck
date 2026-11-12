<?php

use App\Http\Controllers\AuditController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');
Route::get('/bericht/{audit}', [AuditController::class, 'show'])->name('audits.show');
