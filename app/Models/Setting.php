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
     * What the game page needs: whether new rounds can start, the notice shown if not, and how the
     * tower behaves in the next round.
     */
    public static function gameStatus(): array
    {
        $settings = static::current();

        return [
            'open' => ! $settings['playing_disabled'],
            'title' => $settings['closed_title'],
            'message' => $settings['closed_message'],
            'physics' => [
                'enabled' => $settings['physics_enabled'],
                'difficulty' => $settings['physics_difficulty'],
            ],
        ];
    }
}
