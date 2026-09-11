<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('voice', function ($user) {
    return $user->is_active
        && $user->is_approved;
});
