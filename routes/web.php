<?php

use Illuminate\Support\Facades\Route;
use EmmanuelSaleem\LaravelChatbot\Http\Controllers\BotQuestionController;
use EmmanuelSaleem\LaravelChatbot\Http\Controllers\BotManController;

Route::get('chatbot', function () {
    return view('laravel-chatbot::pages.botman-chat');
})->name('chatbot');

// Bot Web Chat Routes
Route::post('botman/web-chat', [BotManController::class, 'webChat'])->name('botman.web-chat');
Route::match(['get', 'post'], 'botman', [BotManController::class, 'handle'])->name('botman.handle');

// Bot Questions Admin Routes
// Note: These routes should be wrapped in auth and admin middleware in your application
// Example: Route::middleware(['auth', 'admin'])->group(function () { ... });
Route::prefix('admin/bot-questions')->name('bot-questions.')->group(function () {
    Route::get('/', [BotQuestionController::class, 'index'])->name('index');
    Route::get('/create', [BotQuestionController::class, 'create'])->name('create');
    Route::post('/', [BotQuestionController::class, 'store'])->name('store');
    Route::get('/import', [BotQuestionController::class, 'import'])->name('import');
    Route::post('/import', [BotQuestionController::class, 'processImport'])->name('import.process');
    Route::get('/{botQuestion}', [BotQuestionController::class, 'show'])->name('show');
    Route::get('/{botQuestion}/edit', [BotQuestionController::class, 'edit'])->name('edit');
    Route::put('/{botQuestion}', [BotQuestionController::class, 'update'])->name('update');
    Route::delete('/{botQuestion}', [BotQuestionController::class, 'destroy'])->name('destroy');
    Route::post('/{botQuestion}/toggle-status', [BotQuestionController::class, 'toggleStatus'])->name('toggle-status');
});
