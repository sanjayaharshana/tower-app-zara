<?php

namespace Tests\Feature;

use App\Models\Play;
use App\Models\Player;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardScreenTest extends TestCase
{
    use RefreshDatabase;

    /** A player with a submitted best, plus the finished round that earned it. */
    private function ranked(string $name, string $phone, int $score, int $height = 5, bool $submitted = true): Player
    {
        $player = Player::create([
            'name' => $name, 'phone' => $phone, 'games_played' => 1,
            'best_score' => $submitted ? $score : 0, 'best_height' => $submitted ? $height : 0, 'best_at' => $submitted ? now() : null,
        ]);
        $play = $player->plays()->create();
        $play->forceFill(['score' => $score, 'height' => $height, 'perfects' => 0, 'finished_at' => now(), 'submitted_at' => $submitted ? now() : null])->save();

        return $player;
    }

    public function test_kiosk_page_renders_with_its_assets(): void
    {
        $this->get('/leaderboard')->assertOk()
            ->assertSee('window.KIOSK', false)
            ->assertSee('game/leaderboard.js', false)
            ->assertSee('game/leaderboard.css', false)
            ->assertSee('leaderboard\/data', false);
    }

    public function test_data_lists_the_board_in_order_with_names_but_never_phone_numbers(): void
    {
        $this->ranked('Kasun', '0771234567', 900, 6);
        $this->travel(1)->seconds();
        $this->ranked('Nimali', '0712345678', 1500, 9);
        $this->travel(1)->seconds();
        $this->ranked('Ravi', '0751112223', 400, 3, submitted: false);

        $res = $this->getJson('/leaderboard/data')->assertOk();

        $res->assertJsonPath('leaderboard.0.name', 'Nimali')
            ->assertJsonPath('leaderboard.0.rank', 1)
            ->assertJsonPath('leaderboard.0.score', 1500)
            ->assertJsonPath('leaderboard.1.name', 'Kasun')
            ->assertJsonCount(2, 'leaderboard')
            ->assertJsonPath('stats.players', 3)
            ->assertJsonPath('stats.games_today', 3)
            ->assertJsonPath('open', true);

        // latest finished rounds first; a submitted best carries its place on the board
        $res->assertJsonCount(3, 'recent')
            ->assertJsonPath('recent.0.name', 'Ravi')
            ->assertJsonPath('recent.0.rank', null)
            ->assertJsonPath('recent.1.name', 'Nimali')
            ->assertJsonPath('recent.1.rank', 1)
            ->assertJsonPath('recent.2.rank', 2);

        $this->assertStringNotContainsString('0771234567', $res->getContent());
        $this->assertStringNotContainsString('phone', $res->getContent());
    }

    public function test_board_shows_the_top_ten_and_ignores_unfinished_rounds(): void
    {
        foreach (range(1, 12) as $i) {
            $this->ranked("Player $i", '07'.str_pad((string) $i, 8, '0', STR_PAD_LEFT), $i * 100);
        }
        Player::first()->plays()->create(); // started, never finished

        $this->getJson('/leaderboard/data')
            ->assertJsonCount(10, 'leaderboard')
            ->assertJsonPath('leaderboard.0.name', 'Player 12')
            ->assertJsonPath('leaderboard.9.name', 'Player 3')
            ->assertJsonCount(8, 'recent')
            ->assertJsonPath('stats.games_today', 12);
    }

    public function test_data_says_when_the_admin_has_disabled_playing(): void
    {
        Setting::saveMany(['playing_disabled' => true, 'closed_title' => 'Back at 6 pm']);

        $this->getJson('/leaderboard/data')
            ->assertJsonPath('open', false)
            ->assertJsonPath('closed_title', 'Back at 6 pm');
    }

    public function test_empty_board(): void
    {
        $this->getJson('/leaderboard/data')->assertOk()
            ->assertJsonCount(0, 'leaderboard')
            ->assertJsonCount(0, 'recent')
            ->assertJsonPath('stats.players', 0);

        $this->assertSame(0, Play::count());
    }
}
