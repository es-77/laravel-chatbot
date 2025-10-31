<?php

use Illuminate\Support\Facades\Route;
use EmmanuelSaleem\LaravelChatbot\Http\Controllers\Api\BotMessageController;

/*
|--------------------------------------------------------------------------
| Chatbot API Routes
|--------------------------------------------------------------------------
|
| These routes handle API requests for the chatbot functionality.
| They can be protected with API authentication middleware in your application.
|
*/

// API Route for chatbot messages
// Note: This route is accessible at /api/chatbot/message
// You may need to add 'api' middleware in your RouteServiceProvider or bootstrap/app.php
Route::prefix('api/chatbot')->name('api.chatbot.')->group(function () {
    // Send message and get bot response
    Route::post('/message', [BotMessageController::class, 'sendMessage'])->name('message');
});
