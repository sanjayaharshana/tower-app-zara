<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40);
            $table->string('phone', 15)->unique();          // canonical 07XXXXXXXX
            $table->unsignedInteger('best_score')->default(0);
            $table->unsignedSmallInteger('best_height')->default(0);
            $table->timestamp('best_at')->nullable();       // null until a score is submitted to the leaderboard
            $table->unsignedInteger('games_played')->default(0);
            $table->timestamps();

            $table->index(['best_score', 'best_height']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
