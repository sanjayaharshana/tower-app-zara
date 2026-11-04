<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\LeaderboardScreenController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GameController::class, 'index'])->name('game');

// Live leaderboard for the portrait kiosk screen; the page polls the data every 3 seconds.
Route::get('leaderboard', [LeaderboardScreenController::class, 'show'])->name('leaderboard.screen');
Route::get('leaderboard/data', [LeaderboardScreenController::class, 'data'])->middleware('throttle:120,1')->name('leaderboard.data');

// Polled by open start screens (every 30s) so they notice the admin opening or closing the game. It's
// a cheap read like the page itself, so it stays out of the throttle that players on one IP share.
Route::get('game/status', [GameController::class, 'status'])->name('game.status');

// JSON endpoints used by public/game/game.js. They're CSRF-exempt (see bootstrap/app.php); the limit
// is generous because players at an event or on mobile data often share one public IP.
Route::middleware('throttle:300,1')->prefix('game')->name('game.')->group(function () {
    Route::post('start', [GameController::class, 'start'])->name('start');
    Route::post('finish', [GameController::class, 'finish'])->name('finish');
    Route::post('submit', [GameController::class, 'submit'])->name('submit');
    Route::get('leaderboard', [GameController::class, 'leaderboard'])->name('leaderboard');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminController::class, 'loginForm'])->name('login');
    // Wrong-password attempts are rate limited inside AdminController::login.
    Route::post('login', [AdminController::class, 'login'])->name('login.post');

    Route::middleware('admin')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('export', [AdminController::class, 'export'])->name('export');
        Route::get('settings', [AdminSettingsController::class, 'edit'])->name('settings');
        Route::put('settings', [AdminSettingsController::class, 'update'])->name('settings.update');
        Route::post('logout', [AdminController::class, 'logout'])->name('logout');
    });
});
