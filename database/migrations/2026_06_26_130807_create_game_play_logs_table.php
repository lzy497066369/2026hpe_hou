<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_play_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('distance')->default(0);
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('duration')->default(0);
            $table->timestamp('played_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('played_at');
            $table->index(['score', 'distance']);
        });

        DB::table('game_records')
            ->orderBy('id')
            ->chunkById(500, function ($records): void {
                $now = now();
                $rows = $records->map(fn ($record): array => [
                    'user_id' => $record->user_id,
                    'distance' => $record->distance,
                    'score' => $record->score,
                    'duration' => $record->duration,
                    'played_at' => $record->played_at,
                    'created_at' => $record->created_at ?? $now,
                    'updated_at' => $record->updated_at ?? $now,
                ])->all();

                if ($rows !== []) {
                    DB::table('game_play_logs')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_play_logs');
    }
};
