<?php

use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::put('locale', [LocaleController::class, 'update'])->name('locale.update');
