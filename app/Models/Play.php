<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Play extends Model
{
    protected $fillable = ['score', 'height', 'perfects', 'finished_at', 'submitted_at'];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'height' => 'integer',
            'perfects' => 'integer',
            'finished_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
