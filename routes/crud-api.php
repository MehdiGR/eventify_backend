<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\GroupController;

Route::prefix('events')->name('events.')->group(function () {
    // Public endpoints
    Route::controller(EventController::class)->group(function () {
        Route::get('/', 'readAll')->name('readAll');
        Route::get('/{id}', 'readOne')->name('readOne');
    });

    // Authenticated endpoints
    Route::middleware('auth:api')->group(function () {
        // Organizer endpoints
        Route::middleware('role:organizer')->group(function () {
            Route::controller(EventController::class)->group(function () {
                Route::post('/', 'createOne')->name('createOne');
                Route::put('/{id}', 'updateOne')->name('updateOne');
                Route::patch('/{id}', 'patchOne')->name('patchOne');
                Route::delete('/{id}', 'deleteOne')->name('deleteOne');
                Route::get('/dashboard/stats', 'organizerStats')->name('organizerStats');
                Route::get('/my-events', 'organizerEvents')->name('organizerEvents');

                // Organizer managing participants
                Route::post('/{id}/participants/add', 'addParticipant')->name('addParticipant');
                Route::delete('/{id}/participants/{user_id}/remove', 'removeParticipant')->name('removeParticipant');
            });
        });

        // Endpoints accessible to both organizers and participants
        Route::get('/{id}/participants', [EventController::class, 'participants'])->name('participants');

        // Participant-specific endpoints
        Route::middleware('role:participant')->group(function () {
            Route::controller(EventController::class)->group(function () {
                // Route to register for an event
                Route::post('/{id}/register', 'register')->name('events.register');

                // Route to unregister from an event
                Route::delete('/{id}/participants/unregister', 'unregister')->name('events.unregister');

                // Route to get the list of events a participant is registered for
                Route::get('/my-events', 'participantsEvents')->name('participantsEvents');
            });
        });
    });
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    // Only admin can handle the group entity for now
    Route::resource('groups', GroupController::class);
});
