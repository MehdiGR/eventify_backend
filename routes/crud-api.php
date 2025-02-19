<?php

use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

Route::prefix('events')->name('events.')->group(function () {
    // Public endpoints
    Route::controller(EventController::class)->group(function () {
        Route::get('/', 'readAll');
        Route::get('/{id}', 'readOne');
    });

    // Authenticated endpoints
    Route::middleware('auth:api')->group(function () {
        // Organizer-specific endpoints
        Route::middleware('role:ORGANIZER')->group(function () {
            Route::controller(EventController::class)->group(function () {
                Route::post('/', 'createOne');
                Route::put('/{id}', 'updateOne');
                Route::patch('/{id}', 'patchOne');
                Route::delete('/{id}', 'deleteOne');

                // Add organizer-specific routes here
                Route::get('/dashboard/stats', 'organizerStats');
                Route::get('/my-events', 'organizerEvents');
            });
        });

        // Participant-specific endpoints
        Route::middleware('role:PARTICIPANT')->group(function () {
            Route::controller(EventController::class)->group(function () {
                Route::post('/register', 'registerForEvent');
            });
        });
    });
});
