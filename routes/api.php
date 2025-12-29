<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

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

});
