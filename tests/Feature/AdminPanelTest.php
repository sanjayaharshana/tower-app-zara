<?php

namespace Tests\Feature;

use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['game.admin_password' => 'test-secret']);
    }

    public function test_admin_pages_require_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/export')->assertRedirect('/admin/login');
    }

    public function test_the_right_password_logs_in_and_a_wrong_one_does_not(): void
    {
        $this->post('/admin/login', ['password' => 'nope'])
            ->assertSessionHasErrors('password')
            ->assertSessionMissing('admin_auth');

        $this->post('/admin/login', ['password' => 'test-secret'])
            ->assertRedirect('/admin')
            ->assertSessionHas('admin_auth', true);
    }

    public function test_successful_logins_never_trigger_the_lockout(): void
    {
        foreach (range(1, 8) as $_) {
            $this->post('/admin/login', ['password' => 'test-secret'])->assertRedirect('/admin');
            $this->post('/admin/logout');
        }
    }

    public function test_too_many_wrong_passwords_lock_the_form_with_a_message_instead_of_a_429(): void
    {
        $this->freezeTime();

        foreach (range(1, 5) as $_) {
            $this->post('/admin/login', ['password' => 'nope'])->assertSessionHasErrors('password');
        }

        $this->from('/admin/login')->post('/admin/login', ['password' => 'test-secret'])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['password' => 'Too many wrong attempts. Please try again in 60 seconds.'])
            ->assertSessionMissing('admin_auth');

        $this->travel(61)->seconds();
        $this->post('/admin/login', ['password' => 'test-secret'])->assertRedirect('/admin');
    }

    public function test_a_correct_login_resets_the_wrong_password_count(): void
    {
        foreach (range(1, 4) as $_) {
            $this->post('/admin/login', ['password' => 'nope']);
        }
        $this->post('/admin/login', ['password' => 'test-secret'])->assertRedirect('/admin');
        $this->post('/admin/logout');

        foreach (range(1, 4) as $_) {
            $this->post('/admin/login', ['password' => 'nope']);
        }
        $this->post('/admin/login', ['password' => 'test-secret'])->assertRedirect('/admin');
    }

    public function test_login_stays_locked_until_a_password_is_configured(): void
    {
        config(['game.admin_password' => '']);

        $this->get('/admin/login')->assertOk()->assertSee('ADMIN_PASSWORD');
        $this->post('/admin/login', ['password' => 'anything'])->assertSessionMissing('admin_auth');
    }

    public function test_admin_sees_players_and_games_and_can_export_them(): void
    {
        $kasun = Player::create([
            'name' => 'Kasun', 'phone' => '0771234567',
            'best_score' => 1050, 'best_height' => 8, 'best_at' => now(), 'games_played' => 3,
        ]);
        $kasun->plays()->create(['score' => 1050, 'height' => 8, 'perfects' => 2, 'finished_at' => now(), 'submitted_at' => now()]);
        Player::create(['name' => '=HYPERLINK("x")', 'phone' => '0712345678']);

        $admin = $this->withSession(['admin_auth' => true]);

        $admin->get('/admin')->assertOk()->assertSee('Kasun')->assertSee('077 123 4567');
        $admin->get('/admin?tab=games')->assertOk()->assertSee('1,050');
        $admin->get('/admin?tab=players&search=0712')->assertOk()->assertSee('HYPERLINK')->assertDontSee('Kasun');

        $csv = $admin->get('/admin/export')->assertOk()->streamedContent();
        $this->assertStringContainsString('1,Kasun,"077 123 4567",1050,8,3,', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv); // neutralised spreadsheet formula
    }
}
