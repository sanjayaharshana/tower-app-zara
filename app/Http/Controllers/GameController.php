<?php

namespace App\Http\Controllers;

use App\Models\Play;
use App\Models\Player;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(): View
    {
        $status = Setting::gameStatus();

        // read by public/game/game.js as window.ZARA
        return view('game', ['status' => $status, 'zara' => [
            'assetBase' => asset('game').'/',
            'status' => $status,
            'routes' => [
                'start' => route('game.start'),
                'finish' => route('game.finish'),
                'submit' => route('game.submit'),
                'leaderboard' => route('game.leaderboard'),
                'status' => route('game.status'),
            ],
        ]]);
    }

    public function status(): JsonResponse
    {
        return response()->json(Setting::gameStatus());
    }

    /**
     * Register (or update) the player by mobile number and open a new round. While the admin has
     * playing disabled no new rounds start, but rounds already under way can still finish and submit.
     */
    public function start(Request $request): JsonResponse
    {
        $status = Setting::gameStatus();
        if (! $status['open']) {
            return response()->json(['closed' => true] + $status, 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = Player::normalizePhone($data['phone']);
        if ($phone === null) {
            throw ValidationException::withMessages(['phone' => 'Enter a valid mobile number.']);
        }

        $player = Player::updateOrCreate(['phone' => $phone], ['name' => trim(strip_tags($data['name']))]);
        $play = $player->plays()->create();

        return response()->json(['play_id' => $play->id]);
    }

    /** Record the result of a round. */
    public function finish(Request $request): JsonResponse
    {
        $data = $request->validate([
            'play_id' => ['required', 'integer'],
            'score' => ['required', 'integer', 'min:0'],
            'height' => ['required', 'integer', 'min:0', 'max:65535'],
            'perfects' => ['required', 'integer', 'min:0', 'max:65535'],
        ]);

        $play = Play::whereKey($data['play_id'])->whereNull('finished_at')->firstOrFail();

        // The browser reports the score, so reject anything the time actually played can't explain.
        $seconds = $play->created_at->diffInSeconds(now(), true);
        if ($seconds > config('game.max_round_seconds')
            || $data['score'] > max(1, $seconds) * config('game.max_points_per_second')) {
            throw ValidationException::withMessages(['score' => 'This result could not be verified.']);
        }

        $play->update([
            'score' => $data['score'],
            'height' => $data['height'],
            'perfects' => $data['perfects'],
            'finished_at' => now(),
        ]);
        $play->player->increment('games_played');

        return response()->json(['ok' => true]);
    }

    /** Put a finished round on the leaderboard (a player keeps their best) and return the standings. */
    public function submit(Request $request): JsonResponse
    {
        $data = $request->validate(['play_id' => ['required', 'integer']]);

        $play = Play::whereKey($data['play_id'])->whereNotNull('finished_at')->firstOrFail();
        $player = $play->player;

        DB::transaction(function () use ($play, $player) {
            if ($play->submitted_at !== null) {
                return;
            }
            $play->update(['submitted_at' => now()]);

            $better = $player->best_at === null
                || $play->score > $player->best_score
                || ($play->score === $player->best_score && $play->height > $player->best_height);
            if ($better) {
                $player->update(['best_score' => $play->score, 'best_height' => $play->height, 'best_at' => now()]);
            }
        });

        return response()->json($this->standings($player));
    }

    public function leaderboard(): JsonResponse
    {
        return response()->json($this->standings());
    }

    /** Top players by name only (never phone numbers), plus the given player's row if they are outside the top. */
    private function standings(?Player $me = null): array
    {
        $top = Player::ranked()->limit(config('game.leaderboard_size'))->get();

        $row = fn (Player $p, int $rank) => [
            'rank' => $rank,
            'name' => $p->name,
            'score' => $p->best_score,
            'height' => $p->best_height,
            'me' => $me !== null && $me->is($p),
        ];

        $standings = ['leaderboard' => $top->values()->map(fn (Player $p, int $i) => $row($p, $i + 1))];
        if ($me?->best_at !== null && ! $top->contains($me)) {
            $standings['you'] = $row($me, $me->rank());
        }

        return $standings;
    }
}
