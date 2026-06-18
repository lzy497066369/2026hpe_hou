<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lottery_records', function (Blueprint $table): void {
            $table->string('source_type')->nullable()->after('prize_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('lottery_records', function (Blueprint $table): void {
            $table->dropIndex(['source_type']);
            $table->dropColumn('source_type');
        });
    }
};
