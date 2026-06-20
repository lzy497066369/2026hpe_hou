<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lottery_records', function (Blueprint $table): void {
            $table->foreignId('work_id')
                ->nullable()
                ->after('prize_id')
                ->constrained('works')
                ->nullOnDelete();
            $table->unique(['prize_id', 'work_id']);
        });
    }

    public function down(): void
    {
        Schema::table('lottery_records', function (Blueprint $table): void {
            $table->dropUnique(['prize_id', 'work_id']);
            $table->dropConstrainedForeignId('work_id');
        });
    }
};
