<?php

namespace Tests\Feature;

use App\Models\Play;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class GameApiTest extends TestCase
{
    use RefreshDatabase;

    private function startRound(string $name = 'Kasun', string $phone = '077 123 4567'): int
    {
        return $this->postJson('/game/start', ['name' => $name, 'phone' => $phone])->assertOk()->json('play_id');
    }

    /** Finish a round after letting enough time pass for the score to be plausible. */
    private function finishRound(int $playId, int $score, int $height = 5): void
    {
        $this->travel(35)->seconds();
        $this->postJson('/game/finish', ['play_id' => $playId, 'score' => $score, 'height' => $height, 'perfects' => 1])
            ->assertOk();
    }

    private function submitRound(int $playId): TestResponse
    {
        return $this->postJson('/game/submit', ['play_id' => $playId])->assertOk();
    }

    public function test_game_page_renders_with_server_config(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('window.ZARA', false)
            ->assertSee('game/game.js', false);
    }

    public function test_start_registers_one_player_per_mobile_number(): void
    {
        $this->startRound('Kasun', '077 123 4567');
        $this->startRound('Kasun P', '+94 77 123-4567');

        $this->assertSame(1, Player::count());
        $this->assertSame(2, Play::count());
        $player = Player::first();
        $this->assertSame('0771234567', $player->phone);
        $this->assertSame('Kasun P', $player->name);
    }

    public function test_start_rejects_invalid_details(): void
    {
        $this->postJson('/game/start', ['name' => 'Kasun', 'phone' => '12345'])
            ->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->postJson('/game/start', ['name' => '', 'phone' => '0771234567'])
            ->assertUnprocessable()->assertJsonValidationErrors('name');

        $this->assertSame(0, Player::count());
    }

    public function test_finish_rejects_a_score_the_round_time_cannot_explain(): void
    {
        $id = $this->startRound();
        $this->travel(10)->seconds();

        $this->postJson('/game/finish', ['play_id' => $id, 'score' => 50000, 'height' => 5, 'perfects' => 0])
            ->assertUnprocessable()->assertJsonValidationErrors('score');
        $this->assertNull(Play::find($id)->finished_at);
    }

    public function test_rounds_have_no_time_limit_but_stale_ones_are_turned_away(): void
    {
        // a long game, played until the lives ran out
        $long = $this->startRound();
        $this->travel(20)->minutes();
        $this->postJson('/game/finish', ['play_id' => $long, 'score' => 48000, 'height' => 160, 'perfects' => 40])->assertOk();

        // a round left open for over an hour
        $stale = $this->startRound();
        $this->travel(61)->minutes();
        $this->postJson('/game/finish', ['play_id' => $stale, 'score' => 500, 'height' => 5, 'perfects' => 0])
            ->assertUnprocessable()->assertJsonValidationErrors('score');
    }

    public function test_a_round_can_only_be_finished_once(): void
    {
        $id = $this->startRound();
        $this->finishRound($id, 900);

        $this->postJson('/game/finish', ['play_id' => $id, 'score' => 9000, 'height' => 9, 'perfects' => 0])->assertNotFound();
        $this->assertSame(900, Play::find($id)->score);
    }

    public function test_an_unfinished_round_cannot_be_submitted(): void
    {
        $id = $this->startRound();

        $this->postJson('/game/submit', ['play_id' => $id])->assertNotFound();
    }

    public function test_submit_keeps_each_players_best_score_and_ranks_them(): void
    {
        $kasun = $this->startRound('Kasun', '0771234567');
        $this->finishRound($kasun, 1050, 8);
        $this->submitRound($kasun)
            ->assertJsonPath('leaderboard.0.name', 'Kasun')
            ->assertJsonPath('leaderboard.0.me', true);

        $nimali = $this->startRound('Nimali', '0712345678');
        $this->finishRound($nimali, 1400, 9);
        $this->submitRound($nimali)
            ->assertJsonPath('leaderboard.0.name', 'Nimali')
            ->assertJsonPath('leaderboard.1.name', 'Kasun')
            ->assertJsonPath('leaderboard.1.me', false);

        // a worse round doesn't lower Kasun's best
        $again = $this->startRound('Kasun', '0771234567');
        $this->finishRound($again, 300, 2);
        $this->submitRound($again)
            ->assertJsonCount(2, 'leaderboard')
            ->assertJsonPath('leaderboard.1.score', 1050)
            ->assertJsonPath('leaderboard.1.me', true);

        $this->assertSame(2, Player::firstWhere('phone', '0771234567')->games_played);
    }

    public function test_a_player_outside_the_top_gets_their_own_rank(): void
    {
        config(['game.leaderboard_size' => 2]);

        foreach ([3000, 2000, 1000] as $i => $score) {
            $id = $this->startRound("Player {$i}", "077000000{$i}");
            $this->finishRound($id, $score);
            $response = $this->submitRound($id);
        }

        $response->assertJsonCount(2, 'leaderboard')
            ->assertJsonPath('you.rank', 3)
            ->assertJsonPath('you.me', true);
    }

    public function test_the_leaderboard_never_exposes_mobile_numbers(): void
    {
        $id = $this->startRound('Kasun', '0771234567');
        $this->finishRound($id, 500);
        $this->submitRound($id);

        $this->getJson('/game/leaderboard')->assertOk()
            ->assertJsonPath('leaderboard.0.name', 'Kasun')
            ->assertDontSee('771234567');
    }
}
