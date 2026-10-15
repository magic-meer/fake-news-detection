<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PredictionController;
use App\Http\Controllers\SettingsController;

Route::get('/', [PredictionController::class, 'index'])->name('home');
Route::post('/predict', [PredictionController::class, 'predict'])->name('predict');
Route::get('/history/{id?}', [App\Http\Controllers\PredictionController::class, 'history'])->name('history');

Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings');
Route::post('/settings/upload', [App\Http\Controllers\SettingsController::class, 'upload'])->name('settings.upload');
Route::post('/settings/retrain', [App\Http\Controllers\SettingsController::class, 'retrain'])->name('settings.retrain');
