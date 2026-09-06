<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentQueueController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Client\DocumentController;
use App\Http\Controllers\Client\ResultDocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
    Route::get('/support', [SupportController::class, 'index'])->name('support.index');
    Route::get('/chat/{publicId}', [ChatController::class, 'show'])->name('chat.show');
    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/{publicId}', [ApplicationController::class, 'show'])->name('applications.show');
    Route::post('/applications/{publicId}/review/start', [ApplicationController::class, 'beginReview'])->name('applications.review.start');
    Route::post('/applications/{publicId}/review/finalize', [ApplicationController::class, 'finalizeReview'])->name('applications.review.finalize');
    Route::post('/applications/{publicId}/estimate', [ApplicationController::class, 'estimate'])->name('applications.estimate');
    Route::post('/applications/{publicId}/external/waiting', [ApplicationController::class, 'waitingExternal'])->name('applications.external.waiting');
    Route::post('/applications/{publicId}/results', [ApplicationController::class, 'uploadResult'])->name('applications.results.upload');
    Route::post('/applications/{publicId}/results/review', [ApplicationController::class, 'beginResultReview'])->name('applications.results.review');
    Route::post('/applications/{publicId}/complete', [ApplicationController::class, 'complete'])->name('applications.complete');
    Route::post('/applications/{publicId}/archive', [ApplicationController::class, 'archive'])->name('applications.archive');
    Route::post('/documents/{documentId}/review', [ApplicationController::class, 'reviewDocument'])->name('documents.review');
    Route::get('/documents', [DocumentQueueController::class, 'index'])->name('documents.index');
    Route::get('/documents/{documentId}/view', [DocumentController::class, 'preview'])->name('documents.view');
    Route::get('/documents/{documentId}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/results/{resultId}/view', [ResultDocumentController::class, 'preview'])->name('results.view');
    Route::get('/results/{resultId}/download', [ResultDocumentController::class, 'download'])->name('results.download');
    Route::post('/results/{resultId}/verify', [ApplicationController::class, 'verifyResult'])->name('results.verify');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{publicId}', [UserController::class, 'show'])->name('users.show');
});
