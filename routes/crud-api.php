<?php

use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(
    function () {
        // Organizer-specific routes
        Route::prefix('events')->name('events.')->group(
            function () {
                Route::controller(EventController::class)->group(
                    function () {
                        Route::post('/', 'createOne')->middleware('role:ORGANIZER'); // Only ORGANIZER can create events
                        Route::get('/{id}', 'readOne');
                        Route::get('/', 'readAll');
                        Route::put('/{id}', 'updateOne')->middleware('role:ORGANIZER'); // Only ORGANIZER can update events
                        Route::patch('/{id}', 'patchOne')->middleware('role:ORGANIZER'); // Only ORGANIZER can patch events
                        Route::delete('/{id}', 'deleteOne')->middleware('role:ORGANIZER'); // Only ORGANIZER can delete events
                        Route::post('/register', 'registerForEvent')->middleware('role:PARTICIPANT'); //
                    }
                );
            }
        );
    }
);
