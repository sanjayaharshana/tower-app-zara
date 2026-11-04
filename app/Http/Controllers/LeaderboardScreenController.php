<?php

namespace App\Http\Controllers;

use App\Models\Play;
use App\Models\Player;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/** The live leaderboard for a portrait 1080×1920 kiosk screen at the event. */
class LeaderboardScreenController extends Controller
{
    /** Players on the screen: three on the podium and the rest in the list below. */
    private const TOP = 10;

    /** Latest finished rounds in the "Just played" ticker. */
    private const RECENT = 8;

    public function show(): View
    {
        // read by public/game/leaderboard.js as window.KIOSK
        return view('leaderboard', ['kiosk' => [
            'dataUrl' => route('leaderboard.data'),
            'playUrl' => route('game'),
            'pollMs' => 3000,
        ]]);
    }

    /** Everything the screen shows, polled every few seconds. Names only, never phone numbers. */
    public function data(): JsonResponse
    {
        $top = Player::ranked()->limit(self::TOP)->get();
        $positions = $top->pluck('id')->flip(); // player id => 0-based position

        $recent = Play::with('player')
            ->whereNotNull('finished_at')
            ->latest('finished_at')->latest('id')
            ->limit(self::RECENT)
            ->get();

        $status = Setting::gameStatus();
        $todayStart = now(config('game.timezone'))->startOfDay()->utc();

        return response()->json([
            'open' => $status['open'],
            'closed_title' => $status['title'],
            'leaderboard' => $top->values()->map(fn (Player $p, int $i) => [
                'id' => $p->id,
                'rank' => $i + 1,
                'name' => $p->name,
                'score' => $p->best_score,
                'height' => $p->best_height,
            ]),
            'recent' => $recent->map(fn (Play $play) => [
                'id' => $play->id,
                'name' => $play->player->name,
                'score' => $play->score,
                'height' => $play->height,
                // the place this round holds on the board, once it's submitted as the player's best
                'rank' => $play->submitted_at !== null
                    && $positions->has($play->player_id)
                    && $play->score === $play->player->best_score
                    ? $positions[$play->player_id] + 1
                    : null,
            ]),
            'stats' => [
                'players' => Player::count(),
                'games_today' => Play::where('finished_at', '>=', $todayStart)->count(),
            ],
        ]);
    }
}
