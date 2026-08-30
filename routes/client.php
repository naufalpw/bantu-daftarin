<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\Client\ApplicationController;
use App\Http\Controllers\Client\DocumentController;
use App\Http\Controllers\Client\ResultDocumentController;
use App\Http\Controllers\Client\ServiceCatalogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'client'])->prefix('app')->name('client.')->group(function (): void {
    Route::get('/dashboard', [ApplicationController::class, 'index'])->name('dashboard');
    Route::get('/services', [ServiceCatalogController::class, 'index'])->name('services.index');
    Route::get('/applications/create/{service}', [ApplicationController::class, 'create'])->name('applications.create');
    Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store');
    Route::get('/applications/{publicId}', [ApplicationController::class, 'show'])->name('applications.show');
    Route::put('/applications/{publicId}', [ApplicationController::class, 'update'])->name('applications.update');
    Route::post('/applications/{publicId}/submit', [ApplicationController::class, 'submit'])->name('applications.submit');
    Route::post('/applications/{publicId}/payment', [ApplicationController::class, 'payment'])->name('applications.payment');
    Route::post('/applications/{publicId}/documents/submit', [ApplicationController::class, 'submitDocuments'])->name('applications.documents.submit');
    Route::post('/applications/{publicId}/revision/submit', [ApplicationController::class, 'submitRevision'])->name('applications.revision.submit');
    Route::post('/applications/{applicationId}/requirements/{requirementId}/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::delete('/documents/{documentId}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/documents/{documentId}/view', [DocumentController::class, 'preview'])->name('documents.view');
    Route::get('/documents/{documentId}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/results/{resultId}/view', [ResultDocumentController::class, 'preview'])->name('results.view');
    Route::get('/results/{resultId}/download', [ResultDocumentController::class, 'download'])->name('results.download');
    Route::get('/chat/{publicId}', [ChatController::class, 'show'])->name('chat.show');
});
