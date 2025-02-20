<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel(
    'App.Models.User.{id}',
    function ($user, $id) {
        return (int) $user->id === (int) $id;
    }
);
Broadcast::channel(
    'events',
    function ($user) {
        return true; // Allow all authenticated users to access the channel
    }
);
Broadcast::channel('organizer.{id}', function ($user, $id) {
    // Check if the user is an organizer and if they match the ID
    return $user->hasRole('organizer') && (int) $user->id === (int) $id;
});
