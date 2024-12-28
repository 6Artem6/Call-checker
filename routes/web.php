<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiteController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/', [SiteController::class, 'index'])->name('home');
Route::middleware('auth')->group(function () {
    Route::get('/request-send', [SiteController::class, 'requestCreate'])->name('request-create');
    Route::post('/request-send', [SiteController::class, 'requestSend'])->name('request-send');
    Route::get('/request-list', [SiteController::class, 'requestList'])->name('request-list');
    Route::get('/file-list/{id?}', [SiteController::class, 'fileList'])->name('file-list');
    Route::get('/file-info/{id}', [SiteController::class, 'fileInfo'])->name('file-info');
    Route::post('/instruction-create', [SiteController::class, 'instructionCreate'])->name('instruction-create');
    Route::post('/instruction-list', [SiteController::class, 'instructionList'])->name('instruction-list');
    Route::get('/file/{id}', [SiteController::class, 'file'])->name('file');
});

//Route::get('cron', [CronController::class, 'index'])->name('cron.index');
//Route::get('cron/file-transcribe', [CronController::class, 'fileTranscribe']);
//Route::get('cron/file-analysis', [CronController::class, 'fileAnalysis']);
