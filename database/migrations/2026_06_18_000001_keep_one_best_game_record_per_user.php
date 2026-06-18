<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->deleteDuplicateGameRecords();

        Schema::table('game_records', function (Blueprint $table): void {
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('game_records', function (Blueprint $table): void {
            $table->dropUnique(['user_id']);
        });
    }

    private function deleteDuplicateGameRecords(): void
    {
        $records = DB::table('game_records')
            ->select(['id', 'user_id'])
            ->orderBy('user_id')
            ->orderByDesc('score')
            ->orderByDesc('id')
            ->get();

        $keptUserIds = [];
        $idsToDelete = [];

        foreach ($records as $record) {
            if (isset($keptUserIds[$record->user_id])) {
                $idsToDelete[] = $record->id;
                continue;
            }

            $keptUserIds[$record->user_id] = true;
        }

        if ($idsToDelete === []) {
            return;
        }

        DB::table('game_records')
            ->whereIn('id', $idsToDelete)
            ->delete();
    }
};
