<?php

return [

    /*
    | Password for the /admin panel. Leave it empty to keep the panel locked.
    */
    'admin_password' => env('ADMIN_PASSWORD', ''),

    /*
    | Times are stored in UTC; the admin panel shows them (and counts "today") in this timezone.
    */
    'timezone' => env('GAME_TIMEZONE', 'Asia/Colombo'),

    /*
    | Number of rows on the public leaderboard.
    */
    'leaderboard_size' => 10,

    /*
    | The browser reports the score, so the server rejects results that the time actually
    | played can't explain. A perfect drop is worth 300 points and the fastest drop cycle
    | is about 0.6 seconds, so no honest round earns more than ~500 points per second.
    */
    'max_points_per_second' => 500,

    /*
    | A round must be finished within this many seconds of starting. Rounds have no time limit
    | (they last until the player runs out of lives), so this only turns away stale rounds.
    */
    'max_round_seconds' => 3600,

];
