<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    /** Sri Lankan mobile numbers; keep in sync with PHONE_PATTERN in public/game/game.js. */
    public const PHONE_PATTERN = '/^(?:\+94|0094|94|0)(7\d{8})$/';

    protected $fillable = ['name', 'phone', 'best_score', 'best_height', 'best_at', 'games_played'];

    protected function casts(): array
    {
        return [
            'best_score' => 'integer',
            'best_height' => 'integer',
            'games_played' => 'integer',
            'best_at' => 'datetime',
        ];
    }

    public function plays(): HasMany
    {
        return $this->hasMany(Play::class);
    }

    /** Players on the leaderboard, best first; ties go to the taller tower, then to whoever got there first. */
    public function scopeRanked(Builder $query): void
    {
        $query->whereNotNull('best_at')
            ->orderByDesc('best_score')
            ->orderByDesc('best_height')
            ->orderBy('best_at')
            ->orderBy('id');
    }

    /** 1-based leaderboard position, using the same ordering as scopeRanked(). */
    public function rank(): int
    {
        return static::query()
            ->whereNotNull('best_at')
            ->where(fn (Builder $q) => $q
                ->where('best_score', '>', $this->best_score)
                ->orWhere(fn (Builder $q) => $q
                    ->where('best_score', $this->best_score)
                    ->where('best_height', '>', $this->best_height))
                ->orWhere(fn (Builder $q) => $q
                    ->where('best_score', $this->best_score)
                    ->where('best_height', $this->best_height)
                    ->where('best_at', '<', $this->best_at)))
            ->count() + 1;
    }

    /** Canonical 07XXXXXXXX form, so +94 77…, 94 77… and 077… are one player; null if not a valid mobile. */
    public static function normalizePhone(string $raw): ?string
    {
        $digits = preg_replace('/[\s()\-]/', '', $raw);

        return preg_match(self::PHONE_PATTERN, $digits, $m) ? '0'.$m[1] : null;
    }

    /** 077 123 4567 — readable, and spreadsheets keep it as text so the leading 0 survives. */
    public function formattedPhone(): string
    {
        return substr($this->phone, 0, 3).' '.substr($this->phone, 3, 3).' '.substr($this->phone, 6);
    }
}
