<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Settings the admin can change from the panel, stored as key/value rows. */
class Setting extends Model
{
    /** Every setting and the value it has until the admin saves one. */
    public const DEFAULTS = [
        'playing_disabled' => false,
        'closed_title' => 'Game closed',
        'closed_message' => "The Tower Challenge isn't open right now.\nPlease check back soon!",
        'physics_enabled' => false,
        'physics_difficulty' => 3,
        'lives' => 7,
        'speed_difficulty' => 3,
        'packs_per_level' => 5,
    ];

    /** Most lives a game can start with. */
    public const MAX_LIVES = 20;

    /** Most packs a player may need to stack to go up a level. */
    public const MAX_PACKS_PER_LEVEL = 20;

    /**
     * How the moving pack speeds up, per speed difficulty. Players go up a level every packs_per_level
     * stacked packs. `start` is the pack's speed at level 1 in game units per second (the game screen is
     * 400 units wide), each level adds `step` times that, and it never goes faster than `max`.
     * The game gets these numbers through gameStatus().
     */
    public const SPEED_LEVELS = [
        1 => ['name' => 'Very easy', 'description' => 'Starts slow and speeds up gently.', 'start' => 170, 'step' => 0.15, 'max' => 380],
        2 => ['name' => 'Easy', 'description' => 'A relaxed start that gets a little faster each level.', 'start' => 200, 'step' => 0.18, 'max' => 460],
        3 => ['name' => 'Normal', 'description' => 'A steady start that gets noticeably faster each level.', 'start' => 230, 'step' => 0.25, 'max' => 560],
        4 => ['name' => 'Hard', 'description' => 'Starts quick and speeds up sharply each level.', 'start' => 260, 'step' => 0.3, 'max' => 640],
        5 => ['name' => 'Very hard', 'description' => 'Starts fast and soon reaches top speed. For experts.', 'start' => 300, 'step' => 0.4, 'max' => 720],
    ];

    /**
     * Tower physics difficulty levels. The game's tuning for each level is PHYSICS_LEVELS in
     * public/game/game.js.
     */
    public const PHYSICS_LEVELS = [
        1 => ['Very easy', 'A slight sway and very forgiving balance.'],
        2 => ['Easy', 'A gentle sway and forgiving balance.'],
        3 => ['Normal', 'A steady sway. Careful stacks stay up; sloppy ones lean over and topple.'],
        4 => ['Hard', 'A strong sway. Packs must land well inside the edge, and tall towers need care.'],
        5 => ['Very hard', 'A big sway that can topple even a good stack. For experts.'],
    ];

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /** All settings, typed like DEFAULTS. */
    public static function current(): array
    {
        $saved = static::query()->pluck('value', 'key');

        $settings = [];
        foreach (self::DEFAULTS as $key => $default) {
            $value = $saved[$key] ?? null;
            $settings[$key] = match (true) {
                $value === null => $default,
                is_bool($default) => $value === '1',
                is_int($default) => (int) $value,
                default => $value,
            };
        }

        return $settings;
    }

    public static function saveMany(array $values): void
    {
        foreach (array_intersect_key($values, self::DEFAULTS) as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => is_bool($value) ? ($value ? '1' : '0') : $value]);
        }
    }

    /**
     * What the game page needs: whether new rounds can start, the notice shown if not, and the rules
     * for the next round (lives, how the moving pack speeds up, and how the tower behaves).
     */
    public static function gameStatus(): array
    {
        $settings = static::current();

        return [
            'open' => ! $settings['playing_disabled'],
            'title' => $settings['closed_title'],
            'message' => $settings['closed_message'],
            'lives' => $settings['lives'],
            'speed' => [
                'difficulty' => $settings['speed_difficulty'],
                'start' => self::SPEED_LEVELS[$settings['speed_difficulty']]['start'],
                'step' => self::SPEED_LEVELS[$settings['speed_difficulty']]['step'],
                'max' => self::SPEED_LEVELS[$settings['speed_difficulty']]['max'],
                'packs_per_level' => $settings['packs_per_level'],
            ],
            'physics' => [
                'enabled' => $settings['physics_enabled'],
                'difficulty' => $settings['physics_difficulty'],
            ],
        ];
    }
}
