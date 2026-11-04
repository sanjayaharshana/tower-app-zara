<?php

namespace Tests\Feature;

use App\Models\Play;
use App\Models\Player;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function disablePlaying(string $title = 'Back at 6 pm', string $message = 'Come back soon!'): void
    {
        Setting::saveMany(['playing_disabled' => true, 'closed_title' => $title, 'closed_message' => $message]);
    }

    public function test_settings_page_requires_admin_login(): void
    {
        $this->get('/admin/settings')->assertRedirect('/admin/login');
        $this->put('/admin/settings', ['playing_disabled' => '1', 'closed_title' => 'x'])->assertRedirect('/admin/login');

        $this->assertFalse(Setting::current()['playing_disabled']);
    }

    public function test_game_is_open_with_default_texts_until_the_admin_changes_them(): void
    {
        $this->getJson('/game/status')->assertOk()->assertExactJson([
            'open' => true,
            'title' => Setting::DEFAULTS['closed_title'],
            'message' => Setting::DEFAULTS['closed_message'],
            'physics' => ['enabled' => false, 'difficulty' => 3],
        ]);

        $this->withSession(['admin_auth' => true])->get('/admin/settings')->assertOk()
            ->assertSee('Disable playing')
            ->assertSee('Game open')
            ->assertSee(Setting::DEFAULTS['closed_title']);
    }

    public function test_admin_can_disable_playing_and_set_the_message(): void
    {
        $this->withSession(['admin_auth' => true])
            ->put('/admin/settings', ['playing_disabled' => '1', 'closed_title' => 'Back at 6 pm', 'closed_message' => "See you\nsoon!", 'physics_difficulty' => '3'])
            ->assertRedirect('/admin/settings')
            ->assertSessionHas('status');

        $this->getJson('/game/status')->assertJson(['open' => false, 'title' => 'Back at 6 pm', 'message' => "See you\nsoon!"]);
        $this->withSession(['admin_auth' => true])->get('/admin')->assertSee('Game closed');

        // the hidden "0" is all that's sent when the checkbox is unticked
        $this->withSession(['admin_auth' => true])
            ->put('/admin/settings', ['playing_disabled' => '0', 'closed_title' => 'Back at 6 pm', 'closed_message' => '', 'physics_difficulty' => '3'])
            ->assertRedirect('/admin/settings');

        $this->getJson('/game/status')->assertJson(['open' => true, 'message' => '']);
    }

    public function test_settings_need_a_title_and_keep_the_texts_short(): void
    {
        $this->withSession(['admin_auth' => true])
            ->put('/admin/settings', ['playing_disabled' => '1', 'closed_title' => '', 'closed_message' => str_repeat('a', 301), 'physics_difficulty' => '6'])
            ->assertSessionHasErrors(['closed_title', 'closed_message', 'physics_difficulty']);

        $this->assertTrue(Setting::gameStatus()['open']);
    }

    public function test_admin_can_switch_on_tower_physics_and_pick_a_difficulty(): void
    {
        $admin = $this->withSession(['admin_auth' => true]);
        $admin->get('/admin/settings')->assertOk()
            ->assertSee('Enable falling physics')
            ->assertSee('physics off, normal game');

        $admin->put('/admin/settings', ['closed_title' => 'Game closed', 'physics_enabled' => '1', 'physics_difficulty' => '5'])
            ->assertRedirect('/admin/settings');

        $this->getJson('/game/status')->assertJsonPath('open', true)
            ->assertJsonPath('physics', ['enabled' => true, 'difficulty' => 5]);
        $admin->get('/admin/settings')->assertSee('physics on · Very hard');
        $this->get('/')->assertSee('"physics":{"enabled":true,"difficulty":5}', false);

        // switching physics off keeps the chosen difficulty for next time
        $admin->put('/admin/settings', ['closed_title' => 'Game closed', 'physics_enabled' => '0', 'physics_difficulty' => '5']);
        $this->getJson('/game/status')->assertJsonPath('physics', ['enabled' => false, 'difficulty' => 5]);
    }

    public function test_start_screen_shows_the_notice_instead_of_the_form_while_playing_is_disabled(): void
    {
        $this->get('/')->assertOk()->assertDontSee('is-closed');

        $this->disablePlaying('Back at <b>6</b>', 'Come back soon!');

        $this->get('/')->assertOk()
            ->assertSee('screen--start active is-closed', false)
            ->assertSee('Back at &lt;b&gt;6&lt;/b&gt;', false)
            ->assertSee('Come back soon!');
    }

    public function test_no_new_rounds_start_while_playing_is_disabled(): void
    {
        $this->disablePlaying();

        $this->postJson('/game/start', ['name' => 'Kasun', 'phone' => '0771234567'])
            ->assertForbidden()
            ->assertJson(['closed' => true, 'open' => false, 'title' => 'Back at 6 pm', 'message' => 'Come back soon!']);

        $this->assertSame(0, Player::count());
        $this->assertSame(0, Play::count());
    }

    public function test_a_round_already_under_way_can_still_finish_and_submit(): void
    {
        $playId = $this->postJson('/game/start', ['name' => 'Kasun', 'phone' => '0771234567'])->assertOk()->json('play_id');

        $this->disablePlaying();

        $this->travel(35)->seconds();
        $this->postJson('/game/finish', ['play_id' => $playId, 'score' => 900, 'height' => 6, 'perfects' => 2])->assertOk();
        $this->postJson('/game/submit', ['play_id' => $playId])->assertOk()->assertJsonPath('leaderboard.0.score', 900);
    }
}
