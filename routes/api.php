<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\DealQuoteController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\CarrierQuoteController;

// Public (unauthenticated)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/create-token', [AuthController::class, 'createToken']);
Route::post('/verify-token', [AuthController::class, 'verifyToken']);
Route::put('/reset-password', [UserController::class, 'resetPassword']);
Route::get('/user-exists-by-email', [UserController::class, 'checkUserExistsByEmail']);

// Protected (authenticated)
Route::middleware('auth:sanctum')->group(function () {
    
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'user']);

    Route::group(['prefix' => 'user'], function () {

        // User self-service updates
        Route::prefix('update')->group(function () {
            Route::put('/name', [UserController::class, 'updateName']);
            Route::put('/password', [UserController::class, 'updatePassword']);
            Route::put('/email', [UserController::class, 'updateEmail']);
        });

        // Post because need to send data in body
        Route::post('/delete-account', [UserController::class, 'deleteAccount']);
    });

    Route::prefix('accounts')->group(function () {
        Route::get('/', [AccountController::class, 'index']);
        Route::post('/', [AccountController::class, 'store']);
        Route::get('/{account}', [AccountController::class, 'show']);
        Route::put('/{account}', [AccountController::class, 'update']);
        Route::delete('/{account}', [AccountController::class, 'destroy']);
        // Get notes for a specific account
        Route::get('/{account}/notes', [NoteController::class, 'indexForAccount']);
    });

    Route::prefix('contacts')->group(function () {
        Route::get('/', [ContactController::class, 'index']);
        Route::post('/', [ContactController::class, 'store']);
        Route::get('/{contact}', [ContactController::class, 'show']);
        Route::put('/{contact}', [ContactController::class, 'update']);
        Route::delete('/{contact}', [ContactController::class, 'destroy']);
    });

    Route::prefix('notes')->group(function () {
        Route::post('/', [NoteController::class, 'store']);
        Route::put('/{note}', [NoteController::class, 'update']);
        Route::delete('/{note}', [NoteController::class, 'destroy']);
    });

    Route::prefix('activities')->group(function () {
        Route::get('/', [ActivityController::class, 'index']);
        Route::post('/', [ActivityController::class, 'store']);
        Route::get('/{activity}', [ActivityController::class, 'show']);
        Route::put('/{activity}', [ActivityController::class, 'update']);
        Route::delete('/{activity}', [ActivityController::class, 'destroy']);
    });

    Route::prefix('tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index']);
        Route::post('/', [TaskController::class, 'store']);
        Route::get('/{task}', [TaskController::class, 'show']);
        Route::put('/{task}', [TaskController::class, 'update']);
        Route::delete('/{task}', [TaskController::class, 'destroy']);
    });

    Route::prefix('deals')->group(function () {

        // Deals
        Route::get('/', [DealController::class, 'index']);
        Route::post('/', [DealController::class, 'store']);
        Route::get('/{deal}', [DealController::class, 'show']);
        Route::put('/{deal}', [DealController::class, 'update']);
        Route::delete('/{deal}', [DealController::class, 'destroy']);

        // ===============================
        // Customer Quotes (Deal Quotes)
        // ===============================
        Route::get('/{deal}/quotes', [DealQuoteController::class, 'index']);
        Route::post('/{deal}/quotes', [DealQuoteController::class, 'store']);
        Route::get('/{deal}/quotes/{quote}', [DealQuoteController::class, 'show']);
        Route::put('/{deal}/quotes/{quote}', [DealQuoteController::class, 'update']);
        Route::delete('/{deal}/quotes/{quote}', [DealQuoteController::class, 'destroy']);

        // ===============================
        // Carrier Quotes
        // ===============================
        Route::get('/{deal}/carrier-quotes', [CarrierQuoteController::class, 'index']);
        Route::post('/{deal}/carrier-quotes', [CarrierQuoteController::class, 'store']);
        Route::get('/{deal}/carrier-quotes/{carrierQuote}', [CarrierQuoteController::class, 'show']);
        Route::put('/{deal}/carrier-quotes/{carrierQuote}', [CarrierQuoteController::class, 'update']);
        Route::delete('/{deal}/carrier-quotes/{carrierQuote}', [CarrierQuoteController::class, 'destroy']);
    });

});
