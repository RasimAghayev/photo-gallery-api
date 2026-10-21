<?php

use App\Http\Controllers\ImageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| The gallery lives under the prefix v1. The reading is open, because a
| gallery is meant to be read, and the writing asks for a token of Sanctum.
|
| A token is issued by the command "php artisan api:token <email>".
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {
    Route::get('image/{image}/file', [ImageController::class, 'file'])
        ->middleware('signed')
        ->name('image.file');

    Route::apiResource('image', ImageController::class)
        ->except(['store', 'update', 'destroy']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('image', ImageController::class)
            ->only(['store', 'update', 'destroy']);
    });
});
