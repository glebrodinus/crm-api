<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\NoteController;
// use App\Http\Controllers\DealController;

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
            Route::put('/phone', [UserController::class, 'updatePhone']);
            Route::put('/cell-phone', [UserController::class, 'updateCellPhone']);
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

    Route::prefix('deals')->group(function () {
        // Deal routes would go here
    });

});
