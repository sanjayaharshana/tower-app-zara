<?php

namespace App\Http\Controllers;

use App\Models\Play;
use App\Models\Player;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    /** Wrong passwords allowed per IP per minute before the login form locks. */
    private const MAX_LOGIN_ATTEMPTS = 5;

    public function loginForm(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('admin_auth') === true) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login', ['configured' => filled(config('game.admin_password'))]);
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        // Only wrong passwords count toward the lockout, and it shows on the form rather than as a bare
        // 429 page, so logging in and out a few times (or one typo too many) doesn't lock the admin out.
        $throttleKey = 'admin-login:'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'password' => "Too many wrong attempts. Please try again in {$seconds} ".Str::plural('second', $seconds).'.',
            ]);
        }

        $password = (string) config('game.admin_password');
        if ($password === '' || ! hash_equals($password, $request->string('password')->toString())) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        $request->session()->put('admin_auth', true);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function dashboard(Request $request): View
    {
        $tab = in_array($request->query('tab'), ['leaderboard', 'players', 'games'], true)
            ? $request->query('tab')
            : 'leaderboard';
        $search = trim((string) $request->query('search', ''));
        $matches = fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%");

        $todayStart = now(config('game.timezone'))->startOfDay()->utc();
        $stats = [
            'players' => Player::count(),
            'games' => Play::whereNotNull('finished_at')->count(),
            'today' => Play::where('finished_at', '>=', $todayStart)->count(),
            'top' => (int) Player::max('best_score'),
        ];

        $rows = match ($tab) {
            'leaderboard' => Player::ranked()->when($search !== '', fn ($q) => $q->where($matches)),
            'players' => Player::latest('updated_at')->when($search !== '', fn ($q) => $q->where($matches)),
            'games' => Play::with('player')->whereNotNull('finished_at')->latest('finished_at')
                ->when($search !== '', fn ($q) => $q->whereHas('player', $matches)),
        };
        $rows = $rows->paginate(25)->withQueryString();
        $gameOpen = Setting::gameStatus()['open'];

        return view('admin.dashboard', compact('tab', 'search', 'stats', 'rows', 'gameOpen'));
    }

    /** All players as CSV: leaderboard players first in rank order, then everyone who never submitted. */
    public function export(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows Sinhala/Tamil names correctly
            $put = fn (array $row) => fputcsv($out, $row, ',', '"', '');

            $put(['Rank', 'Name', 'Mobile', 'Best score', 'Tower height', 'Games played', 'Best score at', 'First played']);

            $local = fn ($t) => $t?->copy()->timezone(config('game.timezone'))->format('Y-m-d H:i');

            $rank = 0;
            foreach (Player::ranked()->cursor() as $p) {
                $put([++$rank, self::csvSafe($p->name), $p->formattedPhone(), $p->best_score, $p->best_height,
                    $p->games_played, $local($p->best_at), $local($p->created_at)]);
            }
            foreach (Player::whereNull('best_at')->orderBy('created_at')->cursor() as $p) {
                $put(['', self::csvSafe($p->name), $p->formattedPhone(), '', '', $p->games_played, '', $local($p->created_at)]);
            }

            fclose($out);
        }, 'zara-tower-players-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Stop spreadsheet apps from running a player's name as a formula. */
    private static function csvSafe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
