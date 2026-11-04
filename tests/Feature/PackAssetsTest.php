<?php

namespace Tests\Feature;

use App\Http\Controllers\LeaderboardScreenController;
use Tests\TestCase;

class PackAssetsTest extends TestCase
{
    /** File name => flavour for the PACKS list of public/game/game.js. */
    private function gamePacks(): array
    {
        preg_match_all("/pack\\('[^']+', '([^']+)', '([^']+)'\\)/", file_get_contents(public_path('game/game.js')), $m);

        return array_combine($m[1], $m[2]);
    }

    public function test_every_flavour_comes_as_a_sachet_and_a_pouch(): void
    {
        $perFlavour = array_count_values($this->gamePacks());

        $this->assertCount(5, $perFlavour);
        $this->assertSame([2], array_values(array_unique($perFlavour)));
    }

    public function test_kiosk_footer_never_pairs_a_flavour_with_itself(): void
    {
        $flavours = $this->gamePacks();

        foreach (LeaderboardScreenController::PACK_PAIRS as [$pouch, $sachet]) {
            $this->assertNotSame($flavours[$pouch], $flavours[$sachet], "$pouch and $sachet are the same flavour");
        }
    }

    public function test_every_pack_in_the_game_exists(): void
    {
        $packs = array_keys($this->gamePacks());

        $this->assertCount(10, $packs, 'five flavours, each as a sachet and a pouch');
        foreach ($packs as $file) {
            $this->assertFileExists(public_path('game/assets/packs/'.$file));
        }
    }

    public function test_kiosk_footer_shows_every_game_pack(): void
    {
        $footer = array_merge(...LeaderboardScreenController::PACK_PAIRS);

        $this->assertEqualsCanonicalizing(array_keys($this->gamePacks()), $footer);
        $this->get('/leaderboard')->assertOk()->assertSee('game/assets/packs/bombay-chai-pouch.webp', false);
    }

    public function test_the_background_stall_picture_exists(): void
    {
        $this->assertMatchesRegularExpression("/const STALL = \\{ src: SERVER\\.assetBase \\+ '([^']+)'/", file_get_contents(public_path('game/game.js')));
        preg_match("/const STALL = \\{ src: SERVER\\.assetBase \\+ '([^']+)'/", file_get_contents(public_path('game/game.js')), $m);

        $this->assertFileExists(public_path('game/'.$m[1]));
    }
}
