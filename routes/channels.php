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

// Default Laravel user channel, which you already have.
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// New Channel: Authorize access to the private.accountant.{id} channel.
// This is the essential part that fixes your 403 error.
// It checks if the user is authenticated, has the 'accounts' role,
// and if their ID matches the one in the channel name.



Broadcast::channel('private.accountant.{accountantId}', function ($user, $accountantId) {
    // Check if the user is authenticated and has the 'accounts' role.
    // Use the `where` method on the roles relationship to check for the name.
    if ($user && $user->roles()->where('name', 'accounts')->exists()) {
        // Return true if the authenticated user's ID matches the accountantId in the channel.
        return (int) $user->id === (int) $accountantId;
    }

    // Return false if the user is not an accountant or not authenticated.
    return false;
});
